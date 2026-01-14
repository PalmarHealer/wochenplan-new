<?php

namespace App\Filament\Resources\LessonTemplateResource\Pages;

use App\Filament\Pages\Day;
use App\Filament\Resources\LessonTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

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
        if (isset($this->form->getState()['origin_day'])) {
            $date = Carbon::parse($this->form->getState()['origin_day'])->format('d.m.Y');

            return Day::getUrl(['date' => $date]);
        }

        return $this->getResource()::getUrl('index');
    }
}
