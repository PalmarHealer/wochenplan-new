<?php

namespace App\Filament\Resources\DayPdfResource\Pages;

use App\Filament\Resources\DayPdfResource;
use App\Models\DayPdf;
use App\Services\PdfExportService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDayPdf extends CreateRecord
{
    protected static string $resource = DayPdfResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Check if PDF already exists for this date
        $existingPdf = DayPdf::where('date', $data['date'])->first();

        if ($existingPdf) {
            Notification::make()
                ->title('PDF existiert bereits')
                ->body('Ein PDF für dieses Datum existiert bereits. Es wird neu generiert.')
                ->warning()
                ->send();

            // Regenerate the existing PDF
            $pdfService = app(PdfExportService::class);
            $binaryContent = $pdfService->generatePdf($data['date']);
            $existingPdf->setPdfContentFromBinary($binaryContent);
            $existingPdf->is_outdated = false;
            $existingPdf->created_by = auth()->id();
            $existingPdf->save();

            return $existingPdf;
        }

        // Generate new PDF
        $pdfService = app(PdfExportService::class);
        $binaryContent = $pdfService->generatePdf($data['date']);

        $dayPdf = new DayPdf();
        $dayPdf->date = $data['date'];
        $dayPdf->setPdfContentFromBinary($binaryContent);
        $dayPdf->is_outdated = false;
        $dayPdf->created_by = auth()->id();
        $dayPdf->save();

        return $dayPdf;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
