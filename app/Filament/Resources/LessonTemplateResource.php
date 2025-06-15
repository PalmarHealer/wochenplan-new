<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonTemplateResource\Pages;
use App\Filament\Resources\LessonTemplateResource\RelationManagers;
use App\Models\LessonTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonTemplateResource extends Resource
{
    protected static ?string $model = LessonTemplate::class;

    protected static ?string $navigationIcon = 'tabler-calendar-repeat';

    protected static ?string $navigationLabel = "Angebot vorlagen";

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Angebot vorlage';

    public static function getPluralLabel(): string
    {
        return 'Angebot vorlagen';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
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
            'index' => Pages\ListLessonTemplates::route('/'),
            'create' => Pages\CreateLessonTemplate::route('/create'),
            'edit' => Pages\EditLessonTemplate::route('/{record}/edit'),
        ];
    }
}
