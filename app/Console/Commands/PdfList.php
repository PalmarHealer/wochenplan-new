<?php

namespace App\Console\Commands;

use App\Models\DayPdf;
use Illuminate\Console\Command;

class PdfList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:list {--outdated : Show only outdated PDFs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all stored PDFs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = DayPdf::with('creator')->orderBy('date', 'desc');

        if ($this->option('outdated')) {
            $query->where('is_outdated', true);
        }

        $pdfs = $query->get();

        if ($pdfs->isEmpty()) {
            $this->info($this->option('outdated') ? 'No outdated PDFs found.' : 'No PDFs found.');

            return 0;
        }

        $headers = ['Date', 'Status', 'Created By', 'Updated At'];

        $rows = $pdfs->map(function ($pdf) {
            return [
                $pdf->date->format('Y-m-d'),
                $pdf->is_outdated ? 'Outdated' : 'Current',
                $pdf->creator?->display_name ?? 'System',
                $pdf->updated_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table($headers, $rows);

        $this->info("Total: {$pdfs->count()} PDF(s)");

        return 0;
    }
}
