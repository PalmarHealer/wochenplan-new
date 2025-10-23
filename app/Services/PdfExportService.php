<?php

namespace App\Services;

use App\Models\DayPdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfExportService
{
    public function __construct(
        private LayoutService $layoutService
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

        if ($dayPdf && !$dayPdf->is_outdated) {
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
            $dayPdf = new DayPdf();
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
            $key = $template->room . '-' . $template->lesson_time;
            $array = $template->toArray();
            $array['assigned_users'] = $template->assignedUsers->pluck('display_name', 'id')->toArray();
            return [$key => $array];
        });

        $lessonLessons = $rawLessons->mapWithKeys(function ($lesson) {
            $key = $lesson->room . '-' . $lesson->lesson_time;
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

        $absences = array_map(fn($entry) => [
            'id' => $entry['user']['id'],
            'display_name' => $entry['user']['display_name']
        ], $rawAbsences->toArray());

        $absences = array_values(array_unique($absences, SORT_REGULAR));

        $pdf = Pdf::view('pdf.day-layout', [
            'date' => $date,
            'layout' => $layout,
            'textSize' => $textSize,
            'lessons' => $lessons,
            'colors' => $colors,
            'absences' => $absences,
        ])
            ->format('a4')
            ->landscape()
            ->margins(2, 2, 2, 2);

        // Save to a temporary file and read the contents
        $tempPath = storage_path('app/temp/pdf-' . uniqid() . '.pdf');

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $pdf->save($tempPath);
        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content;
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
