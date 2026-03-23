<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use App\Services\PdfExportService;

class ExportDayPdf implements AiChatTool
{
    public function name(): string
    {
        return 'export_day_pdf';
    }

    public function displayName(): string
    {
        return 'Tages-PDF exportieren';
    }

    public function description(): string
    {
        return 'Export day schedule as PDF. Returns download link.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'Date (YYYY-MM-DD)',
                ],
            ],
            'required' => ['date'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_day::pdf';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $date = $arguments['date'];

        $pdfService = app(PdfExportService::class);
        $pdfService->getOrGeneratePdf($date);

        return [
            'success' => true,
            'download_url' => route('assistant.pdf', ['date' => $date]),
            'message' => "PDF für {$date} wurde erstellt.",
        ];
    }
}
