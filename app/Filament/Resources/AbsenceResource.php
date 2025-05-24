<?php

namespace App\Filament\Resources;

use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use App\Filament\Resources\AbsenceResource\Pages;
use App\Models\Absence;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AbsenceResource extends Resource
{
    protected static ?string $model = Absence::class;

    protected static ?string $navigationIcon = 'tabler-user';

    protected static ?string $navigationLabel = 'Krankmeldungen';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Krankmeldung';

    public static function getPluralLabel(): string
    {
        return 'Krankmeldungen';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    DateRangePicker::make('date')
                        ->label('Datum')
                        ->displayFormat('DD.MM.YYYY')
                        ->format('d.m.Y')
                        ->disableRanges()
                        ->startDate(Carbon::now())
                        ->endDate(Carbon::now())
                        ->required(),
                    Forms\Components\Select::make('user_id')
                        ->label('Benutzer')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->relationship('user', 'name'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start')
                    ->label('Start')
                    ->date("d.m.Y")
                    ->sortable(),
                Tables\Columns\TextColumn::make('end')
                    ->label('Ende')
                    ->date("d.m.Y")
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Geändert von')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->date("d.m.Y H:i")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert am')
                    ->date("d.m.Y H:i")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->preload()
                    ->relationship('user', 'name'),
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
            'index' => Pages\ListAbsences::route('/'),
            'create' => Pages\CreateAbsence::route('/create'),
            'edit' => Pages\EditAbsence::route('/{record}/edit'),
        ];
    }
}
