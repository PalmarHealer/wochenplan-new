<?php

namespace App\Filament\Exports;

use App\Models\ActivityLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ActivityLogExporter extends Exporter
{
    protected static ?string $model = ActivityLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('timestamp')
                ->label('Zeit')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i:s') : ''),

            ExportColumn::make('user.display_name')
                ->label('Benutzer')
                ->formatStateUsing(fn ($state, $record) => $state ?? ($record->user?->display_name ?? 'System')),

            ExportColumn::make('action')
                ->label('Aktion'),

            ExportColumn::make('action_category')
                ->label('Kategorie'),

            ExportColumn::make('resource_label')
                ->label('Ressource')
                ->formatStateUsing(fn ($state) => preg_replace('/^[^:]+:\s*/', '', $state ?? '')),

            ExportColumn::make('resource_type')
                ->label('Ressourcentyp'),

            ExportColumn::make('resource_id')
                ->label('Ressourcen-ID'),

            ExportColumn::make('method')
                ->label('HTTP-Methode'),

            ExportColumn::make('ip_address')
                ->label('IP-Adresse'),

            ExportColumn::make('url')
                ->label('URL'),

            ExportColumn::make('user_agent')
                ->label('User-Agent'),

            ExportColumn::make('is_suspicious')
                ->label('Verdächtig')
                ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein'),

            ExportColumn::make('notes')
                ->label('Notizen'),

            ExportColumn::make('content')
                ->label('Inhalt')
                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Der Activity Log Export wurde mit ' . number_format($export->successful_rows) . ' ' . str('Zeile')->plural($export->successful_rows) . ' abgeschlossen.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('Zeile')->plural($failedRowsCount) . ' konnten nicht exportiert werden.';
        }

        return $body;
    }
}