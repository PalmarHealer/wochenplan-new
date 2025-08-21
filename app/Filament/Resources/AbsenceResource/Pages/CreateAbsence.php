<?php

namespace App\Filament\Resources\AbsenceResource\Pages;

use App\Filament\Resources\AbsenceResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAbsence extends CreateRecord
{
    protected static string $resource = AbsenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $parts = explode(" - ", $data['date'] ?? '');

        // Convert d.m.Y -> Y-m-d for DB storage
        if (count($parts) === 2) {
            try {
                $data['start'] = Carbon::createFromFormat('d.m.Y', trim($parts[0]))->toDateString();
            } catch (\Throwable) {
                $data['start'] = null;
            }
            try {
                $data['end'] = Carbon::createFromFormat('d.m.Y', trim($parts[1]))->toDateString();
            } catch (\Throwable) {
                $data['end'] = null;
            }
        }

        if (! auth()->user()->can('view_any_absence')) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
