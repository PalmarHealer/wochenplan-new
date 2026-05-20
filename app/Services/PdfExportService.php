<?php

namespace App\Services;

use App\Models\DayPdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfExportService
{
    public function __construct(
        private LayoutService $layoutService,
        private LunchService $lunchService,
    ) {}

    /**
     * Get or generate PDF for a specific date
     * Returns base64 encoded PDF content
     */
    public function getOrGeneratePdf(string|Carbon $date): string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateString = $date->format('Y-m-d');

        // Check if PDF exists and is not outdated
        $dayPdf = DayPdf::whereDate('date', $dateString)->first();

        if ($dayPdf && ! $dayPdf->is_outdated) {
            return $dayPdf->pdf_content;
        }

        // Generate new PDF
        $pdfContent = $this->generatePdf($date);

        if ($dayPdf) {
            // Update existing outdated PDF
            $dayPdf->setPdfContentFromBinary($pdfContent);
            $dayPdf->is_outdated = false;
            $dayPdf->created_by = Auth::id();
            $dayPdf->save();
        } else {
            // Create new PDF record
            $dayPdf = new DayPdf;
            $dayPdf->date = $dateString;
            $dayPdf->setPdfContentFromBinary($pdfContent);
            $dayPdf->is_outdated = false;
            $dayPdf->created_by = Auth::id();
            $dayPdf->save();
        }

        return $dayPdf->pdf_content;
    }

    /**
     * Generate PDF for a specific date
     * Returns binary PDF content
     */
    public function generatePdf(string|Carbon $date): string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $layoutWithModel = $this->layoutService->getLayoutWithModelForDate($date);
        $layout = $layoutWithModel['data'];
        $layoutModel = $layoutWithModel['model'];
        $textSize = $layoutModel?->text_size ?? 100.0;

        // Get lessons data similar to Day.php
        $rawLessons = \App\Models\Lesson::with(['assignedUsers'])
            ->whereDate('date', $date->toDateString())
            ->get();

        $parentIds = $rawLessons->pluck('parent_id')->filter()->unique();

        $rawLessonTemplates = \App\Models\LessonTemplate::with(['assignedUsers'])
            ->where('weekday', $date->format('N'))
            ->where('created_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $date);
            })
            ->get();

        $filteredTemplates = $rawLessonTemplates->reject(function ($template) use ($parentIds) {
            return $parentIds->contains($template->id);
        });

        $templateLessons = $filteredTemplates->mapWithKeys(function ($template) {
            $key = $template->room.'-'.$template->lesson_time;
            $array = $template->toArray();
            $array['assigned_users'] = $template->assignedUsers->pluck('display_name', 'id')->toArray();

            return [$key => $array];
        });

        $lessonLessons = $rawLessons->mapWithKeys(function ($lesson) {
            $key = $lesson->room.'-'.$lesson->lesson_time;
            $array = $lesson->toArray();
            $array['assigned_users'] = $lesson->assignedUsers->pluck('display_name', 'id')->toArray();

            return [$key => $array];
        });

        if (empty($templateLessons->values()->toArray())) {
            $merged = $lessonLessons;
        } else {
            $merged = $templateLessons->merge($lessonLessons);
        }

        $lessons = $merged->values()->toArray();

        // Get colors
        $colors = \App\Models\Color::all()->pluck('color', 'id')->toArray();

        // Get absences
        $rawAbsences = \App\Models\Absence::with(['user'])
            ->whereDate('start', '<=', $date)
            ->whereDate('end', '>=', $date)
            ->get();

        $absences = array_map(fn ($entry) => [
            'id' => $entry['user']['id'],
            'display_name' => $entry['user']['display_name'],
        ], $rawAbsences->toArray());

        $absences = array_values(array_unique($absences, SORT_REGULAR));

        // Get lunch for placeholder replacement (e.g. %mittagessen%)
        $lunch = $this->lunchService->getLunch($date->toDateString());

        // Set up writable directory for Chrome user data.
        // Per-invocation subdir so concurrent generatePdf() calls (e.g. the
        // scheduled `yesterday` and `today` jobs running back-to-back) don't
        // collide on Chromium's user-data-dir lock.
        $userDataDir = storage_path('app/chrome-data/'.$date->format('Y-m-d').'-'.uniqid('', true));
        if (! file_exists($userDataDir)) {
            mkdir($userDataDir, 0755, true); // Secure permissions (owner rwx, group/others rx)
        }

        $pdf = Pdf::view('pdf.day-layout', [
            'date' => $date,
            'layout' => $layout,
            'textSize' => $textSize,
            'lessons' => $lessons,
            'colors' => $colors,
            'absences' => $absences,
            'lunch' => $lunch,
        ])
            ->format('a4')
            ->landscape()
            ->margins(2, 2, 2, 2)
            ->withBrowsershot(function ($browsershot) use ($userDataDir) {
                if ($chromePath = config('laravel-pdf.browsershot.chrome_path')) {
                    $browsershot->setChromePath($chromePath);
                }

                $browsershot
                    ->deviceScaleFactor(2)
                    ->setEnvironmentOptions([
                        'HOME' => $userDataDir,
                        'XDG_CONFIG_HOME' => $userDataDir,
                        'XDG_CACHE_HOME' => $userDataDir,
                        'TMPDIR' => $userDataDir,
                    ])
                    ->addChromiumArguments([
                        'disable-dev-shm-usage',
                        'disable-gpu',
                        'headless=new',
                        'user-data-dir='.$userDataDir,
                        'disable-software-rasterizer',
                        'use-gl=swiftshader',
                    ]);

            });

        // Save to a temporary file and read the contents
        $tempPath = storage_path('app/temp/pdf-'.uniqid().'.pdf');

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        try {
            $pdf->save($tempPath);
            $content = file_get_contents($tempPath);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            // Best-effort cleanup of the per-run Chrome profile dir.
            $this->removeDirectoryRecursive($userDataDir);
        }

        return $content;
    }

    /**
     * Recursively delete a directory and its contents. Errors are swallowed
     * because this runs in a finally block; leftover dirs are harmless and
     * will simply be ignored on the next run (each has a unique name).
     */
    private function removeDirectoryRecursive(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            @($entry->isDir() && ! $entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname()));
        }

        @rmdir($path);
    }

    /**
     * Get existing PDF for a specific date without regenerating
     * Returns base64 encoded PDF content or null if not found
     */
    public function getExistingPdf(string|Carbon $date): ?string
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        $dayPdf = DayPdf::whereDate('date', $dateString)->first();

        return $dayPdf?->pdf_content;
    }

    /**
     * Mark PDFs as outdated for a specific date
     */
    public function markAsOutdated(string|Carbon $date): void
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        DayPdf::whereDate('date', $dateString)->update(['is_outdated' => true]);
    }

    /**
     * Delete PDF for a specific date
     */
    public function deletePdf(string|Carbon $date): bool
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        return DayPdf::whereDate('date', $dateString)->delete() > 0;
    }
}
