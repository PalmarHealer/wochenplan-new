<?php

namespace App\Filament\Pages;

use App\Services\LunchService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FAQ extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'tabler-progress-help';

    protected static ?string $navigationLabel = 'Hilfe';

    protected static ?string $slug = 'faq';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Administration';

    public function getTitle(): string
    {
        return 'Hilfe/Anleitungen';
    }

    protected ?string $subheading = 'In diesem Bereich findest du Anleitungen und Antworten zu häufig gestellten Fragen und bekannten Problemen.
    Diese Sammlung wird kontinuierlich erweitert. Wenn dir ein wiederkehrendes Problem oder eine häufige Frage auffällt,
    sende bitte eine Nachricht an support@nauren.de, damit der Inhalt entsprechend ergänzt werden kann.';

    protected static string $view = 'filament.pages.faq';

    public function clearLunchAction(): Action
    {
        return Action::make('clearLunch')
            ->label('Löschen')
            ->icon('tabler-trash')
            ->color('danger')
            ->form([
                DatePicker::make('date')
                    ->label('Datum auswählen')
                    ->required()
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->closeOnDateSelection(),
            ])
            ->modalHeading('Mittagessen löschen')
            ->modalDescription('Wähle das Datum aus, für das das Mittagessen gelöscht werden soll. Es wird beim nächsten Abruf neu von der API geladen.')
            ->modalSubmitActionLabel('Löschen')
            ->action(function (array $data, LunchService $lunchService) {
                $date = $data['date'];

                $cleared = $lunchService->clearLunch($date);

                if ($cleared) {
                    Notification::make()
                        ->success()
                        ->title('Erfolgreich gelöscht')
                        ->body('Mittagessen für '.$date.' wurde gelöscht und wird beim nächsten Abruf neu geladen.')
                        ->send();
                } else {
                    Notification::make()
                        ->warning()
                        ->title('Nicht gefunden')
                        ->body('Kein Mittagessen für '.$date.' gefunden.')
                        ->send();
                }
            })
            ->visible(fn () => auth()->user()->can('update_layout'));
    }
}
