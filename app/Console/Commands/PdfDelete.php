<?php

namespace App\Console\Commands;

use App\Services\PdfExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PdfDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:delete {date : The date of the PDF to delete (format: Y-m-d or "today")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a PDF for a specific date';

    /**
     * Execute the console command.
     */
    public function handle(PdfExportService $pdfService): int
    {
        $dateInput = $this->argument('date');

        try {
            $date = $dateInput === 'today' ? Carbon::today() : Carbon::parse($dateInput);
        } catch (\Exception $e) {
            $this->error("Invalid date format: {$dateInput}");
            $this->info('Please use format Y-m-d (e.g., 2026-01-16) or "today"');

            return 1;
        }

        $deleted = $pdfService->deletePdf($date);

        if ($deleted) {
            $this->info("PDF for {$date->format('Y-m-d')} deleted successfully");

            return 0;
        } else {
            $this->warn("No PDF found for {$date->format('Y-m-d')}");

            return 0;
        }
    }
}
