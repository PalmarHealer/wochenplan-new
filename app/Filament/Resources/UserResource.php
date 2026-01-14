<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'tabler-user';

    protected static ?string $navigationLabel = 'Benutzer';

    protected static ?int $navigationSort = 99;

    protected static ?string $label = 'Benutzer';

    public static function getPluralLabel(): string
    {
        return 'Benutzer';
    }

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {

        $isCreate = $form->getOperation() === 'create';

        return $form
            ->schema([
                Section::make([
                    Forms\Components\TextInput::make('display_name')
                        ->label('Anzeigename')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required(),
                    Forms\Components\TextInput::make('password')
                        ->label('Passwort')
                        ->password()
                        ->dehydrated(fn ($state) => filled($state))
                        ->required($isCreate)
                        ->nullable(! $isCreate),
                ])->columns(2),
                Section::make([
                    Forms\Components\TextInput::make('email')
                        ->label('E-Mail')
                        ->email()
                        ->required(),
                    Forms\Components\DateTimePicker::make('email_verified_at')
                        ->label('E-Mail verifiziert am')
                        ->native(false)
                        ->default(now())
                        ->displayFormat('d.m.Y H:i')
                        ->format('Y-m-d H:i:s'),
                ])->columns(2),

                Section::make([
                    Forms\Components\Select::make('roles')
                        ->label('Rolle')
                        ->relationship('roles', 'name')
                        ->preload()
                        ->searchable(),
                ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Anzeigename')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->icon('tabler-mail')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('E-Mail Adresse kopiert')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Berechtigung')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $role = is_array($state) ? $state[0] ?? null : $state;

                        return $role
                            ? collect(explode('_', $role))
                                ->map(fn ($word) => ucfirst($word))
                                ->implode(' ')
                            : '-';
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('E-Mail verifiziert am')
                    ->dateTime()
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rolle')
                    ->options(function () {
                        $options = Role::all()->pluck('name', 'id')->mapWithKeys(function ($name, $id) {
                            $formatted = collect(explode('_', $name))
                                ->map(fn ($word) => ucfirst($word))
                                ->implode(' ');

                            return [$id => $formatted];
                        })->toArray();

                        // Add option to filter users without any role
                        return ['none' => 'Ohne Rolle'] + $options;
                    })
                    ->query(function ($query, $state) {
                        // Normalize state across Filament versions
                        $value = is_array($state) ? ($state['value'] ?? null) : $state;

                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === 'none') {
                            return $query->whereDoesntHave('roles');
                        }

                        return $query->whereHas('roles', fn ($q) => $q->where('id', $value));
                    })
                    ->native(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assignRoles')
                    ->label('Berechtigung vergeben')
                    ->icon('tabler-user-plus')
                    ->form([
                        Forms\Components\Select::make('roles')
                            ->label('Rolle auswählen')
                            ->preload()
                            ->searchable()
                            ->options(
                                // Use role names as values for assignRole()
                                Role::all()->pluck('name', 'name')->mapWithKeys(function ($name) {
                                    $formatted = collect(explode('_', $name))
                                        ->map(fn ($word) => ucfirst($word))
                                        ->implode(' ');

                                    return [$name => $formatted];
                                })->toArray()
                            ),
                    ])
                    ->action(function ($records, array $data, Tables\Actions\BulkAction $action) {
                        $roles = $data['roles'] ?? [];

                        if (empty($roles)) {
                            foreach ($records as $user) {
                                $user->roles()->detach();
                            }
                        } else {
                            foreach ($records as $user) {
                                $user->syncRoles($roles);
                            }
                        }

                        $action->deselectRecordsAfterCompletion();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
