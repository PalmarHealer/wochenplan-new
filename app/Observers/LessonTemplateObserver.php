<?php

namespace App\Observers;

use App\Models\DayPdf;
use App\Models\LessonTemplate;

class LessonTemplateObserver
{
    /**
     * Handle the LessonTemplate "created" event.
     */
    public function created(LessonTemplate $template): void
    {
        $this->markRelatedPdfsAsOutdated($template);
    }

    /**
     * Handle the LessonTemplate "updated" event.
     */
    public function updated(LessonTemplate $template): void
    {
        $this->markRelatedPdfsAsOutdated($template);

        // If weekday changed, also mark PDFs for the old weekday
        if ($template->isDirty('weekday')) {
            $originalWeekday = $template->getOriginal('weekday');
            if ($originalWeekday) {
                $this->markPdfsForWeekday($originalWeekday);
            }
        }
    }

    /**
     * Handle the LessonTemplate "deleted" event.
     */
    public function deleted(LessonTemplate $template): void
    {
        $this->markRelatedPdfsAsOutdated($template);
    }

    /**
     * Handle the LessonTemplate "restored" event.
     */
    public function restored(LessonTemplate $template): void
    {
        $this->markRelatedPdfsAsOutdated($template);
    }

    /**
     * Mark all PDFs as outdated for dates matching this template's weekday
     */
    private function markRelatedPdfsAsOutdated(LessonTemplate $template): void
    {
        if ($template->weekday) {
            $this->markPdfsForWeekday($template->weekday);
        }
    }

    /**
     * Mark all PDFs for a specific weekday as outdated
     */
    private function markPdfsForWeekday(int $weekday): void
    {
        $driver = config('database.default');
        $connectionDriver = config("database.connections.{$driver}.driver");

        if ($connectionDriver === 'sqlite') {
            DayPdf::whereRaw('cast(strftime(\'%w\', date) as integer) = ?', [($weekday % 7)])
                ->update(['is_outdated' => true]);
        } else {
            DayPdf::whereRaw('DAYOFWEEK(date) = ?', [($weekday % 7) + 1])
                ->update(['is_outdated' => true]);
        }
    }
}
