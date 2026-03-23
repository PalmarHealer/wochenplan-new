<?php

use App\Http\Controllers\AiChatStreamController;
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

// AI Chat streaming endpoint
Route::post('/assistant/stream', [AiChatStreamController::class, 'stream'])
    ->name('assistant.stream')
    ->middleware(['auth']);

// AI Chat PDF download
Route::get('/assistant/pdf', function (\Illuminate\Http\Request $request) {
    $request->validate(['date' => 'required|date']);

    if (! $request->user()->can('view_day::pdf')) {
        abort(403, 'Keine Berechtigung.');
    }

    $date = \Carbon\Carbon::parse($request->input('date'));
    $pdfService = app(\App\Services\PdfExportService::class);

    // Past dates: only serve existing PDFs; today/future: generate if needed
    $base64 = $date->isPast() && ! $date->isToday()
        ? $pdfService->getExistingPdf($date->toDateString())
        : $pdfService->getOrGeneratePdf($date->toDateString());

    if (! $base64) {
        abort(404, 'PDF nicht verfügbar.');
    }
    $binary = base64_decode($base64, true);
    if ($binary === false) {
        abort(500, 'PDF-Daten fehlerhaft.');
    }
    $filename = $date->locale(config('app.locale'))->translatedFormat('l, d.m.Y').'.pdf';

    return response()->streamDownload(fn () => print ($binary), $filename, ['Content-Type' => 'application/pdf']);
})->name('assistant.pdf')->middleware(['auth']);
