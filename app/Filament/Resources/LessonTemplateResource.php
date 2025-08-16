<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonTemplateResource\Pages;
use App\Forms\Components\CustomRichEditor;
use App\Forms\Components\LayoutSelector;
use App\Models\Color;
use App\Models\LessonTemplate;
use App\Services\LayoutService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonTemplateResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = LessonTemplate::class;

    protected static ?string $navigationIcon = 'tabler-calendar-repeat';

    protected static ?string $navigationLabel = "Wiederholendes Angebot";

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Wiederholendes Angebot';

    public static function getPluralLabel(): string
    {
        return 'Wiederholendes Angebot';
    }

    public static function form(Form $form): Form
    {
        $colors = Color::all()->pluck('color', 'id')->toArray();
        $colors['default'] = 'rgba(0, 0, 0, 0.10)';


        return $form
            ->schema([
                Section::make('Angebot details')
                    ->schema([
                        CustomRichEditor::make('name')
                            ->label('Name')
                            ->toolbarButtons([
                                'bold',
                                'h2',
                                'h3',
                                'italic',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                        CustomRichEditor::make('description')
                            ->label('Beschreibung')
                            ->extraAttributes([
                                'style' => 'min-height: 2.5rem;',
                            ])
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                    ]),
                Section::make([
                    LayoutSelector::make('layout')
                        ->label('Slot')
                        ->columnSpanFull()
                        ->required()
                        ->layout(fn(Get $get) => app(LayoutService::class)->getLayoutByWeekday((int) ($get('weekday') ?? 6)))
                        ->reactive()
                        ->colors($colors),
                ]),
                Section::make([
                    Forms\Components\Select::make('weekday')
                        ->label('Tag')
                        ->native(false)
                        ->options([
                            1 => 'Montag',
                            2 => 'Dienstag',
                            3 => 'Mittwoch',
                            4 => 'Donnerstag',
                            5 => 'Freitag',
                        ])
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('color')
                        ->label('Farbe')
                        ->relationship('colors', 'name')
                        ->native(false)
                        ->preload(),
                ])->columns(2),
                Section::make([
                    Forms\Components\Select::make('assignedUsers')
                        ->label('Personen')
                        ->relationship('assignedUsers', 'name')
                        ->multiple()
                        ->preload()
                        ->disabled(! auth()->user()->can('view_any_lesson::template'))
                        ->visible(auth()->user()->can('view_any_lesson::template')),
                ])->visible(auth()->user()->can('view_any_lesson::template')),
                Section::make([
                    Forms\Components\TextInput::make('notes')
                        ->columnSpanFull()
                        ->label('Notizen'),
                ]),
                ToggleButtons::make('disabled')
                    ->label('Aktiviert')
                    ->required()
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->options([
                        true => 'Deaktiviert',
                        false => 'Aktiviert',
                    ])
                    ->icons([
                        true => 'heroicon-o-x-mark',
                        false => 'heroicon-o-check',
                    ])
                    ->colors([
                        true => 'warning',
                        false => 'success',
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->html(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->sortable()
                    ->searchable()
                    ->html(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notizen')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('weekday')
                    ->label('Tag')
                    ->sortable()
                    ->formatStateUsing(function (?int $state) {
                        $days = [
                            1 => 'Montag',
                            2 => 'Dienstag',
                            3 => 'Mittwoch',
                            4 => 'Donnerstag',
                            5 => 'Freitag',
                            6 => 'Samstag',
                            7 => 'Sonntag',
                        ];
                        return $days[$state] ?? 'Unbekannt';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('rooms.name')
                    ->label('Raum')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('times.name')
                    ->label('Zeit')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignedUsers')
                    ->label('Zugewiesen')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                    $record->assignedUsers->pluck('name')->join(', ')
                    ),
                Tables\Columns\ColorColumn::make('colors.color')
                    ->label('Farbe')
                    ->sortable(),
                Tables\Columns\IconColumn::make('disabled')
                    ->label('Aktiviert')
                    ->sortable()
                    ->getStateUsing(fn ($record) => !$record->disabled)
                    ->boolean(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Geändert von')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime("d.m.Y H:i")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert am')
                    ->dateTime("d.m.Y H:i")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('disabled')
                    ->label('Aktiviert')
                    ->native(false)
                    ->options([
                        true => 'Deaktiviert',
                        false => 'Aktiviert',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        if ($user->can('view_any_lesson::template')) {
            return $query;
        }

        if ($user->can('view_lesson::template')) {
            return $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessonTemplates::route('/'),
            'create' => Pages\CreateLessonTemplate::route('/create'),
            'edit' => Pages\EditLessonTemplate::route('/{record}/edit'),
        ];
    }
}
