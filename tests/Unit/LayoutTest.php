<?php

use App\Models\Layout;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('normalizes weekdays to unique weekday values between one and five', function () {
    $layout = Layout::create([
        'name' => 'A',
        'layout' => json_encode([['x']]),
        'weekdays' => [0, 1, 2, 2, 5, 6, '3'],
    ]);

    expect($layout->fresh()->weekdays)->toBe([1, 2, 5, 3]);
});

it('removes weekday overlap from other layouts when saving', function () {
    $first = Layout::create([
        'name' => 'First',
        'layout' => json_encode([['a']]),
        'weekdays' => [1, 2, 3],
    ]);

    $second = Layout::create([
        'name' => 'Second',
        'layout' => json_encode([['b']]),
        'weekdays' => [3, 4],
    ]);

    expect($first->fresh()->weekdays)->toBe([1, 2]);
    expect($second->fresh()->weekdays)->toBe([3, 4]);
});
