<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class PersonalInfo extends MyProfileComponent
{
    protected string $view = 'filament-breezy::livewire.personal-info';

    public ?array $data = [];

    public $user;

    public $userClass;

    public bool $hasAvatars;

    public array $only = ['display_name', 'name', 'email'];

    public static $sort = 10;

    public ?string $newApiToken = null;

    public function mount(): void
    {
        $this->user = Filament::getCurrentPanel()->auth()->user();
        $this->userClass = get_class($this->user);
        $this->hasAvatars = filament('filament-breezy')->hasAvatars();

        if ($this->hasAvatars) {
            $this->only[] = filament('filament-breezy')->getAvatarUploadComponent()->getStatePath(false);
        }

        $this->form->fill($this->user->only($this->only));
    }

    protected function getProfileFormSchema(): array
    {
        $groupFields = Forms\Components\Group::make($this->getProfileFormComponents())
            ->columnSpan(2);

        return ($this->hasAvatars)
            ? [filament('filament-breezy')->getAvatarUploadComponent(), $groupFields]
            : [$groupFields];
    }

    protected function getProfileFormComponents(): array
    {
        return [
            $this->getDisplayNameComponent(),
            $this->getNameComponent(),
            $this->getEmailComponent(),
            $this->getApiTokenPreviewComponent(),
            $this->getLastLoginInfoComponent(),
        ];
    }

    protected function getDisplayNameComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('display_name')
            ->required()
            ->label('Anzeigename');
    }

    protected function getNameComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->required()
            ->label(__('filament-breezy::default.fields.name'));
    }

    protected function getEmailComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('email')
            ->required()
            ->email()
            ->unique($this->userClass, ignorable: $this->user)
            ->label(__('filament-breezy::default.fields.email'));
    }

    protected function getApiTokenPreviewComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('api_token_preview')
            ->label('API Token')
            ->disabled()
            ->dehydrated(false)
            ->formatStateUsing(function () {
                if ($this->newApiToken) {
                    return $this->newApiToken;
                }

                return $this->user->api_token_last_8
                    ? '********'.(string) $this->user->api_token_last_8
                    : 'Noch kein API-Token erstellt';
            })
            ->helperText('Beim Rotieren wird der neue Token nur einmal vollständig angezeigt.')
            ->suffixAction(
                Action::make('rotateApiToken')
                    ->label('Token rotieren')
                    ->icon('heroicon-m-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('API-Token rotieren')
                    ->modalDescription('Alte API-Tokens werden ungültig und ein neuer Token wird erstellt.')
                    ->action(fn () => $this->rotateApiToken())
            )
            ->columnSpanFull();
    }

    protected function getLastLoginInfoComponent(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('api_last_login_info')
            ->label('API Zugriff (6-Monats-Regel)')
            ->content(function () {
                $lastLogin = $this->user->last_login_at?->format('d.m.Y H:i');
                $isExpired = $this->user->lastLoginExpiredForApi();

                if (! $lastLogin) {
                    return 'Kein erfolgreicher Login gespeichert. API/MCP Zugriff wird bis zum nächsten Login blockiert.';
                }

                if ($isExpired) {
                    return 'Letzter Login: '.$lastLogin.'. Zugriff abgelaufen – bitte erneut im Web anmelden.';
                }

                return 'Letzter Login: '.$lastLogin.'. API/MCP Zugriff ist aktiv.';
            })
            ->columnSpanFull();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getProfileFormSchema())->columns(2)
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = collect($this->form->getState())->only($this->only)->all();
        $this->user->update($data);
        $this->sendNotification();
    }

    public function rotateApiToken(): void
    {
        $this->user->tokens()->delete();

        $plainTextToken = $this->user->createToken('api-mcp')->plainTextToken;

        $this->user->forceFill([
            'api_token_last_rotated_at' => now(),
            'api_token_last_8' => substr($plainTextToken, -8),
        ])->save();

        $this->newApiToken = $plainTextToken;

        Notification::make()
            ->success()
            ->title('Neuer API-Token erstellt')
            ->body('Der Token wird nur einmal vollständig angezeigt. Bitte jetzt sicher speichern.')
            ->send();
    }

    protected function sendNotification(): void
    {
        Notification::make()
            ->success()
            ->title(__('filament-breezy::default.profile.personal_info.notify'))
            ->send();
    }
}
