<?php

namespace App\Filament\Resources\TimeResource\Pages;

use App\Filament\Resources\TimeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTime extends CreateRecord
{
    protected static string $resource = TimeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
