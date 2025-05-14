<?php

namespace App\Filament\Resources\AbsenceResource\Pages;

use App\Filament\Resources\AbsenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAbsence extends EditRecord
{
    protected static string $resource = AbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        $parts = explode(" - ", $data['date']);

        $data['start'] = $parts[0];
        $data['end'] = $parts[1];

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['start'], $data['end'])) {
            $data['date'] = $data['start'] . ' - ' . $data['end'];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
