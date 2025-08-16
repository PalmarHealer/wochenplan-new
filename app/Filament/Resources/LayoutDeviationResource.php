<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LayoutDeviationResource\Pages;
use App\Models\LayoutDeviation;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class LayoutDeviationResource extends Resource
{
    protected static ?string $model = LayoutDeviation::class;

    protected static ?string $navigationIcon = 'tabler-calendar-bolt';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $label = 'Layout Abweichung';

    public static function getPluralLabel(): string
    {
        return 'Layout Abweichungen';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    DateRangePicker::make('date')
                        ->label('Zeitraum')
                        ->displayFormat('DD.MM.YYYY')
                        ->format('d.m.Y')
                        ->disableRanges()
                        ->startDate(Carbon::now())
                        ->endDate(Carbon::now())
                        ->required(),
                    Forms\Components\Select::make('layout_id')
                        ->label('Layout')
                        ->relationship('layout', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start')
                    ->label('Start')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end')
                    ->label('Ende')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('layout.name')
                    ->label('Layout')
                    ->sortable()
                    ->searchable(),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLayoutDeviations::route('/'),
            'create' => Pages\CreateLayoutDeviation::route('/create'),
            'edit' => Pages\EditLayoutDeviation::route('/{record}/edit'),
        ];
    }
}
