<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Forms\Components\CustomRichEditor;
use App\Forms\Components\LayoutSelector;
use App\Models\Color;
use App\Models\Layout;
use App\Models\Lesson;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'tabler-calendar-dot';

    protected static ?string $navigationLabel = "Angebote";

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Angebot';


    public static function getPluralLabel(): string
    {
        return 'Angebote';
    }

    public static function form(Form $form): Form
    {
        $colors = Color::all()->pluck('color', 'id')->toArray();
        $colors['default'] = 'rgba(0, 0, 0, 0.10)';

        $layout = json_decode(Layout::where('active', true)
            ->limit(1)
            ->pluck('layout')
            ->first());

        return $form
            ->schema([
                Section::make('Angebot details')
                    ->columns(2)
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
                        ->layout($layout)
                        ->colors($colors),
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
                        ->native(false)
                        ->preload()
                        ->required(),
                ])->columns(2),
                Section::make([
                    Forms\Components\Select::make('assignedUsers')
                        ->label('Personen')
                        ->relationship('assignedUsers', 'name')
                        ->multiple()
                        ->preload()
                        ->disabled(! auth()->user()->can('view_any_lesson'))
                        ->visible(auth()->user()->can('view_any_lesson')),
                ])->visible(auth()->user()->can('view_any_lesson')),
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

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        if ($user->can('view_any_lesson')) {
            return $query;
        }

        if ($user->can('view_lesson')) {
            return $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', ($user->id ?? null));
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
            'delete',
            'delete_any',
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
