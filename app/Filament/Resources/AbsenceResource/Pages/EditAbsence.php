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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['start']) && !empty($data['end'])) {
            try {
                $start = Carbon::parse($data['start'])->format('d.m.Y');
            } catch (\Throwable) {
                $start = (string) $data['start'];
            }
            try {
                $end = Carbon::parse($data['end'])->format('d.m.Y');
            } catch (\Throwable) {
                $end = (string) $data['end'];
            }
            $data['date'] = $start . ' - ' . $end;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
