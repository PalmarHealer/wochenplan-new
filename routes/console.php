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

// Archive the current day's PDF at 23:59 on weekdays.
// Output is logged (not ->runInBackground()) so failures are visible in
// storage/logs/schedule-pdf.log instead of being silently discarded.
Schedule::command('pdf:generate today')
    ->weekdays()
    ->at('23:59')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-pdf.log'));
