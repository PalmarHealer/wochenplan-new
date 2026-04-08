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

// Generate PDFs for previous and current day at 01:00 on weekdays
Schedule::command('pdf:generate yesterday')
    ->weekdays()
    ->at('01:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// On Mondays, also refresh Friday's PDF to cover weekend gap
Schedule::command('pdf:generate last friday')
    ->mondays()
    ->at('01:02')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command('pdf:generate today')
    ->weekdays()
    ->at('01:05')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
