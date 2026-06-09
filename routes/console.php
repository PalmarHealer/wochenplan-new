<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Generate each weekday's PDF late on that same day (23:00), once the day is
// effectively over and no one is still changing the plan for it. Because school
// days are exactly the weekdays we generate, running same-day leaves no weekend
// gap, so no separate "yesterday" or Monday Friday-refresh job is needed.
// Output is logged (not ->runInBackground()) so failures are visible in
// storage/logs/schedule-pdf.log instead of being silently discarded.
Schedule::command('pdf:generate today')
    ->weekdays()
    ->at('23:00')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-pdf.log'));
