<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PdfExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PdfGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:generate {date : The date to generate PDF for (format: Y-m-d or "today")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a PDF for a specific date';

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

        $this->info("Generating PDF for {$date->format('Y-m-d')}...");

        try {
            // Authenticate as the first user for the created_by field
            $user = User::first();
            if ($user) {
                auth()->login($user);
            }

            $pdfService->getOrGeneratePdf($date);

            $this->info("PDF generated successfully for {$date->format('Y-m-d')}");

            return 0;
        } catch (\Exception $e) {
            $this->error('PDF generation failed: '.$e->getMessage());

            return 1;
        }
    }
}
