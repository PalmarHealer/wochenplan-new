<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Forms\Components\CustomRichEditor;
use App\Forms\Components\LayoutEditor;
use App\Models\Lesson;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'tabler-calendar';

    protected static ?string $navigationLabel = "Angebote";

    protected static ?string $label = 'Angebote';

    public static function getPluralLabel(): string
    {
        return 'Angebote';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_lesson');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_lesson');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_lesson');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_lesson');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_any_lesson');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Angebot details')
                    ->columns(2)
                    ->schema([
                        CustomRichEditor::make('name')
                            ->label('Name')
                            ->required()
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
                            ->required()
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
                    LayoutEditor::make('layout')
                        ->label('Slot')
                        ->columnSpanFull()
                        ->required()
                        ->layout([
                            [
                                ['customName' => 'Test', 'colspan' => 2, 'rowspan' => 1, 'color' => 1, 'attributes' => ['room' => 1, 'lesson_time' => 2]],
                                ['customName' => 'A3', 'attributes' => ['room' => 2, 'lesson_time' => 2]],
                                ['customName' => 'B3', 'attributes' => ['room' => 3, 'lesson_time' => 3]],
                                ['customName' => 'B3', 'attributes' => ['room' => 4, 'lesson_time' => 4]],
                                ['customName' => 'B3', 'attributes' => ['room' => 5, 'lesson_time' => 5]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 6]],
                                ['customName' => 'B3', 'attributes' => ['room' => 7, 'lesson_time' => 7]],
                                ['customName' => 'B3', 'attributes' => ['room' => 8, 'lesson_time' => 8]],
                                ['customName' => 'B3', 'attributes' => ['room' => 9, 'lesson_time' => 9]],
                                ['customName' => 'B3', 'attributes' => ['room' => 10, 'lesson_time' => 10]],
                                ['customName' => 'B3', 'attributes' => ['room' => 11, 'lesson_time' => 11]],
                                ['customName' => 'B3', 'attributes' => ['room' => 12, 'lesson_time' => 12]],
                                ['customName' => 'B3', 'attributes' => ['room' => 13, 'lesson_time' => 13]],
                            ],
                            [
                                ['customName' => 'B1', 'attributes' => ['room' => 4, 'lesson_time' => 50]],
                                ['customName' => 'B2', 'attributes' => ['room' => 5, 'lesson_time' => 60]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                                ['customName' => 'B3', 'attributes' => ['room' => 6, 'lesson_time' => 70]],
                            ],
                        ])
                        ->colors([
                            'default' => '#ffffff',
                            1 => '#fcd34d',
                            2 => '#60a5fa',
                        ]),
                ]),
                Section::make([
                    Forms\Components\DatePicker::make('date')
                        ->label('Datum')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->format('Y-m-d')
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('color')
                        ->label('Farbe')
                        ->relationship('colors', 'name')
                        ->preload()
                        ->required(),
                ])->columns(2),
                Section::make([
                    Forms\Components\Select::make('assignedUsers')
                        ->label('Personen')
                        ->relationship('assignedUsers', 'name')
                        ->multiple()
                        ->preload()
                        ->required(),
                ]),
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
                        false => 'Aktiviert',
                        true => 'Deaktiviert',
                    ])
                    ->icons([
                            false => 'heroicon-o-check',
                            true => 'heroicon-o-x-mark',
                    ])
                    ->colors([
                        false => 'success',
                        true => 'warning',
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->html(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->html(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notizen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Datum')
                    ->date("d.m.Y")
                    ->searchable(),
                Tables\Columns\TextColumn::make('rooms.name')
                    ->label('Raum')
                    ->searchable(),
                Tables\Columns\TextColumn::make('times.name')
                    ->label('Zeit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignedUsers')
                    ->label('Zugewiesen')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                    $record->assignedUsers->pluck('name')->join(', ')
                    ),
                Tables\Columns\ColorColumn::make('colors.color')
                    ->label('Farbe'),
                Tables\Columns\IconColumn::make('disabled')
                    ->label('Aktiviert')
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
                    ->dateTime()
                    ->date("d.m.Y H:i")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert am')
                    ->dateTime()
                    ->date("d.m.Y H:i")
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
            ])
            //->actions([
            //    Tables\Actions\ViewAction::make(),
            //    Tables\Actions\EditAction::make(),
            //])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            //'view' => Pages\ViewLesson::route('/{record}'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
