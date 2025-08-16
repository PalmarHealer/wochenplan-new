<?php

namespace App\Filament\Resources\LayoutDeviationResource\Pages;

use App\Filament\Resources\LayoutDeviationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLayoutDeviation extends CreateRecord
{
    protected static string $resource = LayoutDeviationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if (! empty($data['date'])) {
            $parts = explode(' - ', $data['date']);
            $data['start'] = $parts[0] ?? null;
            $data['end'] = $parts[1] ?? null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
