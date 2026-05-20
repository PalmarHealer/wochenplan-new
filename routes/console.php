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

// Generate PDFs for previous and current day at 01:00 on weekdays.
// Output is logged (not ->runInBackground()) so failures are visible in
// storage/logs/schedule-pdf.log instead of being silently discarded.
Schedule::command('pdf:generate yesterday')
    ->weekdays()
    ->at('01:00')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-pdf.log'));

// On Mondays, also refresh Friday's PDF to cover weekend gap
Schedule::command('pdf:generate last friday')
    ->mondays()
    ->at('01:02')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-pdf.log'));

Schedule::command('pdf:generate today')
    ->weekdays()
    ->at('01:05')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-pdf.log'));
