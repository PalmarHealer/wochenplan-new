<?php

use App\Models\Lunch;
use App\Services\LunchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns cached lunch without calling external api when available in database', function () {
    Lunch::create([
        'date' => '2026-03-20',
        'lunch' => 'Pasta',
    ]);

    $service = new LunchService;

    expect($service->getLunch('2026-03-20'))->toBe('Pasta');
});

it('clears lunch for date and returns true when record existed', function () {
    Lunch::create([
        'date' => '2026-03-21',
        'lunch' => 'Soup',
    ]);

    $service = new LunchService;

    expect($service->clearLunch('2026-03-21'))->toBeTrue();
    expect(Lunch::whereDate('date', '2026-03-21')->exists())->toBeFalse();
});

it('returns false when clearing lunch for a non-existing date', function () {
    $service = new LunchService;

    expect($service->clearLunch('2026-03-22'))->toBeFalse();
});
