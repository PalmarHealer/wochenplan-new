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

// Archive the current day's PDF at 23:59 on weekdays
Schedule::command('pdf:generate today')
    ->weekdays()
    ->at('23:59')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
