<?php

namespace App\Filament\Resources\LessonTemplateResource\Pages;

use App\Filament\Resources\LessonTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLessonTemplate extends CreateRecord
{
    protected static string $resource = LessonTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $layout = json_decode($data['layout'], true);
        $data['room'] = $layout['room'];
        $data['lesson_time'] = $layout['lesson_time'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
