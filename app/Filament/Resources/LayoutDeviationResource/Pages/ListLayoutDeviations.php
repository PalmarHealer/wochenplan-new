<?php

namespace App\Filament\Resources\LayoutDeviationResource\Pages;

use App\Filament\Resources\LayoutDeviationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLayoutDeviations extends ListRecords
{
    protected static string $resource = LayoutDeviationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
