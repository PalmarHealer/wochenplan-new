<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class TestPdfGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:test {--date=today : The date to generate PDF for (default: today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PDF generation for debugging purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting PDF generation test...');

        try {
            // Parse the date
            $dateString = $this->option('date');
            $date = $dateString === 'today' ? Carbon::today() : Carbon::parse($dateString);

            $this->info("Generating PDF for date: {$date->format('Y-m-d')}");

            // Test 1: Simple Browsershot test
            $this->info('Test 1: Simple Browsershot test...');
            $testHtml = '<html><body><h1>Test PDF</h1><p>This is a test.</p></body></html>';

            // Set up writable directories for Chrome
            $userDataDir = storage_path('app/chrome-data');
            if (! file_exists($userDataDir)) {
                mkdir($userDataDir, 0777, true);
            }

            $browsershot = \Spatie\Browsershot\Browsershot::html($testHtml)
                ->noSandbox()
                ->addChromiumArguments([
                    'disable-dev-shm-usage',
                    'disable-gpu',
                    'headless=new',
                    'user-data-dir='.$userDataDir,
                ]);

            if ($chromePath = config('laravel-pdf.browsershot.chrome_path')) {
                $browsershot->setChromePath($chromePath);
            }

            $pdf = $browsershot->pdf();

            $this->info('✓ Simple Browsershot test successful!');

            // Test 2: Spatie Laravel PDF
            $this->info('Test 2: Testing Spatie Laravel PDF...');
            $pdf2 = \Spatie\LaravelPdf\Facades\Pdf::view('pdf.day-layout', [
                'date' => $date,
                'layout' => [],
                'textSize' => 100,
                'lessons' => [],
                'colors' => [],
                'absences' => [],
            ])
                ->format('a4')
                ->landscape()
                ->margins(2, 2, 2, 2)
                ->withBrowsershot(function ($browsershot) use ($userDataDir) {
                    if ($chromePath = config('laravel-pdf.browsershot.chrome_path')) {
                        $browsershot->setChromePath($chromePath);
                    }

                    $browsershot
                        ->noSandbox()
                        ->addChromiumArguments([
                            'disable-dev-shm-usage',
                            'disable-gpu',
                            'headless=new',
                            'user-data-dir='.$userDataDir,
                        ]);
                })
                ->base64();

            $this->info('✓ Spatie Laravel PDF test successful!');

            // Test 3: Full PdfExportService
            $this->info('Test 3: Testing PdfExportService...');
            $pdfService = app(\App\Services\PdfExportService::class);

            // First, ensure we have a user for the created_by field
            $user = \App\Models\User::first();
            if (! $user) {
                $this->error('No users found in database. Creating a test user...');
                $user = \App\Models\User::create([
                    'name' => 'Test User',
                    'email' => 'test@test.com',
                    'password' => bcrypt('password'),
                    'display_name' => 'Test User',
                ]);
            }

            // Authenticate as this user
            auth()->login($user);

            $pdfContent = $pdfService->generatePdf($date);
            $this->info('✓ PdfExportService test successful!');
            $this->info('PDF Content length: '.strlen($pdfContent).' bytes');

            // Save to file for inspection
            $outputPath = storage_path('app/test-pdf-'.$date->format('Y-m-d').'.pdf');
            file_put_contents($outputPath, $pdfContent);
            $this->info("PDF saved to: {$outputPath}");

            $this->info('');
            $this->info('All tests passed successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('PDF generation failed!');
            $this->error('Error: '.$e->getMessage());
            $this->error('File: '.$e->getFile().':'.$e->getLine());
            $this->error('');
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
