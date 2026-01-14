<?php

namespace App\Observers;

use App\Models\Absence;
use App\Models\DayPdf;
use Carbon\Carbon;

class AbsenceObserver
{
    /**
     * Handle the Absence "created" event.
     */
    public function created(Absence $absence): void
    {
        $this->markRelatedPdfsAsOutdated($absence);
    }

    /**
     * Handle the Absence "updated" event.
     */
    public function updated(Absence $absence): void
    {
        $this->markRelatedPdfsAsOutdated($absence);

        // If start or end date changed, also mark old date range
        if ($absence->isDirty('start') || $absence->isDirty('end')) {
            $originalStart = $absence->getOriginal('start');
            $originalEnd = $absence->getOriginal('end');

            if ($originalStart && $originalEnd) {
                $this->markDateRange($originalStart, $originalEnd);
            }
        }
    }

    /**
     * Handle the Absence "deleted" event.
     */
    public function deleted(Absence $absence): void
    {
        $this->markRelatedPdfsAsOutdated($absence);
    }

    /**
     * Handle the Absence "restored" event.
     */
    public function restored(Absence $absence): void
    {
        $this->markRelatedPdfsAsOutdated($absence);
    }

    /**
     * Mark all PDFs in the absence date range as outdated
     */
    private function markRelatedPdfsAsOutdated(Absence $absence): void
    {
        if ($absence->start && $absence->end) {
            $this->markDateRange($absence->start, $absence->end);
        }
    }

    /**
     * Mark PDFs in a date range as outdated
     */
    private function markDateRange(string $start, string $end): void
    {
        DayPdf::whereBetween('date', [
            Carbon::parse($start)->toDateString(),
            Carbon::parse($end)->toDateString(),
        ])->update(['is_outdated' => true]);
    }
}
