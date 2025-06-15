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
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                    ->columns(2)
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
                    Forms\Components\TextInput::make('notes')
                        ->columnSpanFull()
                        ->label('Notizen'),
                ]),
                ToggleButtons::make('active')
                    ->label('Aktives layout')
                    ->required()
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->options([
                        false => 'Deaktiviert',
                        true => 'Aktiviert',
                    ])
                    ->icons([
                        true => 'heroicon-o-check',
                        false => 'heroicon-o-x-mark',
                    ])
                    ->colors([
                        true => 'success',
                        false => 'warning',
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
                Tables\Columns\IconColumn::make('active')
                    ->label('Aktiviert')
                    ->getStateUsing(fn ($record) => $record->active)
                    ->boolean(),
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
                SelectFilter::make('active')
                    ->label('Aktiviert')
                    ->native(false)
                    ->options([
                        false => 'Deaktiviert',
                        true => 'Aktiviert',
                    ]),
            ])
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
            'index' => Pages\ListLayouts::route('/'),
            'create' => Pages\CreateLayout::route('/create'),
            'edit' => Pages\EditLayout::route('/{record}/edit'),
        ];
    }
}
