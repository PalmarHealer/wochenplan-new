<?php

namespace App\Filament\Resources\DayPdfResource\Pages;

use App\Filament\Resources\DayPdfResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDayPdf extends EditRecord
{
    protected static string $resource = DayPdfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
