<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LayoutResource\Pages;
use App\Forms\Components\CustomRichEditor;
use App\Forms\Components\LayoutEditor;
use App\Models\Color;
use App\Models\Layout;
use App\Models\Room;
use App\Models\Time;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LayoutResource extends Resource
{
    protected static ?string $model = Layout::class;

    protected static ?string $navigationIcon = 'tabler-layout';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        $colors = Color::all()->pluck('color', 'id')->toArray();
        $colors['default'] = 'rgba(0, 0, 0, 0.10)';

        $colorNames = Color::all()->pluck('name', 'id')->toArray();
        $roomNames = Room::all()->pluck('name', 'id')->toArray();
        $timeNames = Time::all()->pluck('name', 'id')->toArray();

        return $form
            ->schema([
                Section::make('Layout details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Name'),
                        Forms\Components\TextInput::make('description')
                            ->label('Beschreibung'),
                    ]),
                Section::make('Layout')
                    ->schema([
                        CustomRichEditor::make('cellContent')
                            ->label('Zellen Text')
                            ->columnSpanFull()
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
                            ])
                            ->dehydrated(false),
                        Forms\Components\Select::make('color')
                            ->label('Farbe')
                            ->options($colorNames)
                            ->preload()
                            ->dehydrated(false),
                        Forms\Components\Select::make('room')
                            ->label('Raum')
                            ->options($roomNames)
                            ->preload()
                            ->dehydrated(false),
                        Forms\Components\Select::make('time')
                            ->label('Zeit')
                            ->options($timeNames)
                            ->preload()
                            ->dehydrated(false),
                        LayoutEditor::make('layout')
                            ->label('')
                            ->columnSpanFull()
                            ->required()
                            ->colors($colors),
                    ])->columns(3),
                Section::make([
                    Forms\Components\Select::make('weekdays')
                        ->label('Gültig für')
                        ->options([
                            1 => 'Montag',
                            2 => 'Dienstag',
                            3 => 'Mittwoch',
                            4 => 'Donnerstag',
                            5 => 'Freitag',
                        ])
                        ->native(false)
                        ->multiple()
                        ->nullable(),
                ]),
                Section::make([
                    Forms\Components\TextInput::make('notes')
                        ->columnSpanFull()
                        ->label('Notizen'),
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
                Tables\Columns\TextColumn::make('weekdays')
                    ->badge()
                    ->label('Gültig für')
                    ->getStateUsing(function ($record) {
                        $map = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];
                        $days = is_array($record->weekdays) ? $record->weekdays : [];
                        return array_values(array_map(fn($d) => $map[$d] ?? (string) $d, $days));
                    }),
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
                SelectFilter::make('weekday')
                    ->label('Tag')
                    ->native(false)
                    ->multiple()
                    ->options([
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $selected = $data['values'] ?? $data['value'] ?? [];
                        if (empty($selected)) {
                            return;
                        }
                        $query->where(function (Builder $q) use ($selected) {
                            foreach ($selected as $day) {
                                $q->orWhereJsonContains('weekdays', (int) $day);
                            }
                        });
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLayouts::route('/'),
            'create' => Pages\CreateLayout::route('/create'),
            'edit' => Pages\EditLayout::route('/{record}/edit'),
        ];
    }
}
