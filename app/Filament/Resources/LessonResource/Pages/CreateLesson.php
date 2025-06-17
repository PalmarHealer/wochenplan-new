<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Pages\Day;
use App\Filament\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    public function getTitle(): string {
        if (request()->has('copy')) return "Angebot bearbeiten";
        return "Angebot erstellen";
    }

    public function getBreadcrumb(): string {
        if (request()->has('copy')) return "bearbeiten";
        return "erstellen";
    }

    public function getSubheading(): string {
        if (request()->has('copy')) return "nur für einen Tag";
        return "";
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $layout = json_decode($data['layout'], true);
        $data['room'] = $layout['room'];
        $data['lesson_time'] = $layout['lesson_time'];

        $data['origin_day'] = null;
        unset($data['origin_day']);

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
