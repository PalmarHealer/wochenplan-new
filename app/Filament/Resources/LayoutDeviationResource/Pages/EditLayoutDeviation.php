<?php

namespace App\Filament\Resources\LayoutDeviationResource\Pages;

use App\Filament\Resources\LayoutDeviationResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLayoutDeviation extends EditRecord
{
    protected static string $resource = LayoutDeviationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        if (! empty($data['date'])) {
            $parts = explode(' - ', $data['date']);
            $data['start'] = $parts[0] ?? null;
            $data['end'] = $parts[1] ?? null;
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['start'], $data['end'])) {
            $data['date'] = Carbon::parse($data['start'])->format('d.m.Y').' - '.Carbon::parse($data['end'])->format('d.m.Y');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
