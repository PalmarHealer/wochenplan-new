<?php

namespace App\Filament\Resources\AbsenceResource\Pages;

use App\Filament\Resources\AbsenceResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAbsence extends EditRecord
{
    protected static string $resource = AbsenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        $parts = explode(" - ", $data['date']);

        $data['start'] = $parts[0];
        $data['end'] = $parts[1];

        if (! auth()->user()->can('view_any_absence')) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['start'], $data['end'])) {
            $data['date'] = Carbon::parse($data['start'])->format('d.m.Y') .' - ' . Carbon::parse($data['end'])->format('d.m.Y');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
