<?php

namespace App\Filament\Resources\LessonTemplateResource\Pages;

use App\Filament\Pages\Day;
use App\Filament\Resources\LessonTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditLessonTemplate extends EditRecord
{
    protected static string $resource = LessonTemplateResource::class;

    public function getSubheading(): string
    {
        return 'für alle Wochen';
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
            'room' => $data['room'],
            'lesson_time' => $data['lesson_time']],
            true);

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
