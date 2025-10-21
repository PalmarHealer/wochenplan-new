<?php

namespace App\Observers;

use App\Models\Lesson;
use App\Services\PdfExportService;

class LessonObserver
{
    /**
     * Handle the Lesson "created" event.
     */
    public function created(Lesson $lesson): void
    {
        app(PdfExportService::class)->markAsOutdated($lesson->date);
    }

    /**
     * Handle the Lesson "updated" event.
     */
    public function updated(Lesson $lesson): void
    {
        app(PdfExportService::class)->markAsOutdated($lesson->date);

        // If the date changed, also mark the old date as outdated
        if ($lesson->isDirty('date')) {
            $originalDate = $lesson->getOriginal('date');
            if ($originalDate) {
                app(PdfExportService::class)->markAsOutdated($originalDate);
            }
        }
    }

    /**
     * Handle the Lesson "deleted" event.
     */
    public function deleted(Lesson $lesson): void
    {
        app(PdfExportService::class)->markAsOutdated($lesson->date);
    }

    /**
     * Handle the Lesson "restored" event.
     */
    public function restored(Lesson $lesson): void
    {
        app(PdfExportService::class)->markAsOutdated($lesson->date);
    }
}
