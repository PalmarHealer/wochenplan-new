<?php

namespace App\Filament\Resources\LessonTemplateResource\Pages;

use App\Filament\Resources\LessonTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLessonTemplate extends EditRecord
{
    protected static string $resource = LessonTemplateResource::class;

    public function getSubheading(): string {
        return "für alle Wochen";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        $layout = json_decode($data['layout'], true);
        $data['room'] = $layout['room'];
        $data['lesson_time'] = $layout['lesson_time'];

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $data['layout'] = json_encode([
            "room" => $data['room'],
            "lesson_time" => $data['lesson_time']],
            true);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
