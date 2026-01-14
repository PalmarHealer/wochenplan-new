<?php

use App\Http\Controllers\LunchController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Lunch management
Route::post('/lunch/clear', [LunchController::class, 'clear'])
    ->name('lunch.clear')
    ->middleware(['auth']);
