<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openSubject')
                ->label('Ressource öffnen')
                ->icon('tabler-external-link')
                ->url(fn () => ActivityLogResource::subjectUrl($this->getRecord()))
                ->visible(fn () => ActivityLogResource::subjectUrl($this->getRecord()) !== null),
            Actions\Action::make('back')
                ->label('Zurück')
                ->url(ActivityLogResource::getUrl('index'))
                ->icon('tabler-arrow-left'),
        ];
    }
}
