<?php

use App\Models\DayPdf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('encodes and decodes pdf content correctly', function () {
    $user = User::factory()->create();

    $dayPdf = DayPdf::create([
        'date' => '2026-03-20',
        'pdf_content' => '',
        'created_by' => $user->id,
    ]);

    $binary = '%PDF-1.7 test content';
    $dayPdf->setPdfContentFromBinary($binary);
    $dayPdf->save();

    expect($dayPdf->fresh()->getDecodedPdfContent())->toBe($binary);
});

it('casts date and is_outdated attributes', function () {
    $dayPdf = DayPdf::create([
        'date' => '2026-03-20',
        'pdf_content' => base64_encode('content'),
        'is_outdated' => 1,
    ]);

    expect($dayPdf->date->toDateString())->toBe('2026-03-20');
    expect($dayPdf->is_outdated)->toBeTrue();
});
