<?php

namespace App\Filament\Resources\LessonTemplateResource\Pages;

use App\Filament\Resources\LessonTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLessonTemplate extends EditRecord
{
    protected static string $resource = LessonTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
