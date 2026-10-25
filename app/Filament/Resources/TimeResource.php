<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeResource\Pages;
use App\Models\Time;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimeResource extends Resource
{
    protected static ?string $model = Time::class;

    protected static ?string $navigationIcon = 'tabler-clock';

    protected static ?string $navigationLabel = 'Zeiten';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Zeit';

    public static function getPluralLabel(): string
    {
        return 'Zeiten';
    }

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('starts_at')
                        ->label('Beginn')
                        ->placeholder('aus dem Namen')
                        ->helperText('Leer lassen, wenn der Name mit der Uhrzeit beginnt, z. B. „10:15 Angebot 1“.')
                        ->regex('/^([01]?\d|2[0-3]):[0-5]\d$/')
                        ->validationMessages(['regex' => 'Bitte als HH:MM angeben, z. B. 10:15.']),
                    Forms\Components\TextInput::make('ends_at')
                        ->label('Ende')
                        ->placeholder('Beginn der nächsten Zeit')
                        ->helperText('Leer lassen, dann endet die Zeit, wenn im Layout des Tages die nächste beginnt.')
                        ->regex('/^([01]?\d|2[0-3]):[0-5]\d$/')
                        ->validationMessages(['regex' => 'Bitte als HH:MM angeben, z. B. 11:00.']),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Beginn')
                    ->state(fn (Time $record) => ($start = $record->startMinutes()) === null ? null : Time::formatMinutes($start))
                    ->placeholder('–'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ende')
                    ->placeholder('nächste Zeit im Layout'),
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

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimes::route('/'),
            'create' => Pages\CreateTime::route('/create'),
            'edit' => Pages\EditTime::route('/{record}/edit'),
        ];
    }
}
