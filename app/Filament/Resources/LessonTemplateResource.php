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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'view' => Pages\ViewLessonTemplate::route('/{record}'),
            'edit' => Pages\EditLessonTemplate::route('/{record}/edit'),
        ];
    }
}
