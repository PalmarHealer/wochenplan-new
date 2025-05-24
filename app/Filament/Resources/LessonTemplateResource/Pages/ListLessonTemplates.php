<?php

namespace App\Filament\Resources\LessonTemplateResource\Pages;

use App\Filament\Resources\LessonTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLessonTemplates extends ListRecords
{
    protected static string $resource = LessonTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
