<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Forms\Components\CustomRichEditor;
use App\Forms\Components\LayoutSelector;
use App\Models\Color;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Services\LayoutService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
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



        $request = request();
        $defaults = [];

        if ($request->has('copy')) {
            $id = $request->input('copy');
            $template = LessonTemplate::with('assignedUsers')->find($id);

            if ($template) {
                $defaults = [
                    'parent_id' => $id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'layout' => $template->layout,
                    'notes' => $template->notes,
                    'disabled' => $template->disabled,
                    'color' => $template->color,
                    'room' => $template->room,
                    'time' => $template->lesson_time,
                    'assignedUsers' => $template->assignedUsers->pluck('id')->toArray(),
                ];
            }
        }

        if ($request->has('room')) $defaults['room'] = $request->input('room');

        if ($request->has('time')) $defaults['time'] = $request->input('time');

        if ($request->has('date')) {
            $defaults['date'] = $request->input('date');
            $defaults['origin_day'] = $defaults['date'];
        }

        if (isset($defaults['room']) and isset($defaults['time'])) {
            $defaults['layout'] = json_encode(['room' => $defaults['room'], 'lesson_time' => $defaults['time']]);
        }

        return $form
            ->schema([
                Forms\Components\Hidden::make('parent_id')
                    ->default($defaults['parent_id'] ?? null),
                Forms\Components\Hidden::make('origin_day')
                    ->afterStateHydrated(function (Forms\Components\Field $component) use ($defaults) {
                        $component->state($defaults['origin_day'] ?? null);
                    }),
                Section::make('Angebot details')
                    ->columns(2)
                    ->schema([
                        CustomRichEditor::make('name')
                            ->default($defaults['name'] ?? '')
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
                            ->default($defaults['description'] ?? '')
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
                    Forms\Components\DatePicker::make('date')
                        ->default(function() use ($defaults) {
                            $date = isset($defaults['date']) ?
                                Carbon::parse($defaults['date']) :
                                now();

                            while ($date->isWeekend()) {
                                $date->addDay();
                            }
                            return $date;
                        })
                        ->label('Datum')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->format('Y-m-d')
                        ->disabledDates(function () {
                            $disabledDates = [];
                            $start = now()->startOfYear();
                            $end = now()->addYear()->endOfYear();

                            while ($start <= $end) {
                                if ($start->isWeekend()) {
                                    $disabledDates[] = $start->format('Y-m-d');
                                }
                                $start->addDay();
                            }

                            return $disabledDates;
                        })
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('color')
                        ->default($defaults['color'] ?? '')
                        ->label('Farbe')
                        ->relationship('colors', 'name')
                        ->native(false)
                        ->preload(),
                ])->columns(2),
                Section::make([
                    LayoutSelector::make('layout')
                        ->default($defaults['layout'] ?? '')
                        ->label('Slot')
                        ->columnSpanFull()
                        ->required()
                        ->layout(fn(Get $get) => app(LayoutService::class)->getLayoutForDate($get('date') ?? now()->toDateString()))
                        ->reactive()
                        ->colors($colors),
                ]),
                Section::make([
                    Forms\Components\Select::make('assignedUsers')
                        ->default($defaults['assignedUsers'] ?? '')
                        ->label('Personen')
                        ->relationship('assignedUsers', 'name')
                        ->multiple()
                        ->preload()
                        ->disabled(! auth()->user()->can('view_any_lesson'))
                        ->visible(auth()->user()->can('view_any_lesson')),
                ])->visible(auth()->user()->can('view_any_lesson')),
                Section::make([
                    Forms\Components\TextInput::make('notes')
                        ->default($defaults['notes'] ?? '')
                        ->columnSpanFull()
                        ->label('Notizen'),
                ]),
                ToggleButtons::make('disabled')
                    ->default($defaults['disabled'] ?? false)
                    ->label('Aktiviert')
                    ->required()
                    ->boolean()
                    ->inline()
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
                Tables\Columns\TextColumn::make('date')
                    ->label('Datum')
                    ->sortable()
                    ->date("d.m.Y")
                    ->searchable(),
                Tables\Columns\TextColumn::make('rooms.name')
                    ->label('Raum')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('times.name')
                    ->label('Zeit')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignedUsers.name')
                    ->label('Zugewiesen')
                    ->sortable()
                    ->searchable()
                    ->separator(', '),
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
            ])
            //->actions([
            //    Tables\Actions\ViewAction::make(),
            //    Tables\Actions\EditAction::make(),
            //])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
