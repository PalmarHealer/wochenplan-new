<?php

namespace App\Filament\Resources\DayPdfResource\Pages;

use App\Filament\Resources\DayPdfResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDayPdfs extends ListRecords
{
    protected static string $resource = DayPdfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
