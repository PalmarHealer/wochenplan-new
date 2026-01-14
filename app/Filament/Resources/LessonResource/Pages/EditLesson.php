<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Pages\Day;
use App\Filament\Resources\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    public function getSubheading(): string
    {
        return 'nur für einen Tag';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    if (isset($this->form->getState()['origin_day'])) {
                        $date = Carbon::parse($this->form->getState()['origin_day'])->format('d.m.Y');
                        $redirect = Day::getUrl(['date' => $date]);
                    } else {
                        $redirect = $this->getResource()::getUrl('index');
                    }

                    return redirect($redirect);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        $layout = json_decode($data['layout'], true);
        $data['room'] = $layout['room'];
        $data['lesson_time'] = $layout['lesson_time'];

        $data['origin_day'] = null;
        unset($data['origin_day']);

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
