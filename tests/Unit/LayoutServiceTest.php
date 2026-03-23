<?php

use App\Models\Layout;
use App\Models\LayoutDeviation;
use App\Services\LayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns empty for invalid weekday values', function () {
    $service = new LayoutService;

    expect($service->getLayoutByWeekday(0))->toBe([]);
    expect($service->getLayoutByWeekday(6))->toBe([]);
});

it('returns most recently updated layout for weekday', function () {
    Layout::create([
        'name' => 'Older',
        'layout' => json_encode([['old']]),
        'weekdays' => [1],
    ]);

    sleep(1);

    Layout::create([
        'name' => 'Newer',
        'layout' => json_encode([['new']]),
        'weekdays' => [1],
    ]);

    $service = new LayoutService;

    expect($service->getLayoutByWeekday(1))->toBe([['new']]);
});

it('returns weekday based layout for a weekday date', function () {
    $layout = Layout::create([
        'name' => 'Weekday',
        'layout' => json_encode([['weekday']]),
        'weekdays' => [1],
    ]);

    $service = new LayoutService;
    $result = $service->getLayoutWithModelForDate('2026-03-16');

    expect($result['data'])->toBe([['weekday']]);
    expect($result['model']?->id)->toBe($layout->id);
});

it('returns empty layout and null model for weekend date', function () {
    Layout::create([
        'name' => 'Only weekday',
        'layout' => json_encode([['weekday']]),
        'weekdays' => [1],
    ]);

    $service = new LayoutService;
    $result = $service->getLayoutWithModelForDate('2026-03-21');

    expect($result['data'])->toBe([]);
    expect($result['model'])->toBeNull();
});

it('prefers deviation layout for date over weekday layout', function () {
    Layout::create([
        'name' => 'Default',
        'layout' => json_encode([['default']]),
        'weekdays' => [1],
    ]);

    $deviationLayout = Layout::create([
        'name' => 'Deviation',
        'layout' => json_encode([['deviation']]),
        'weekdays' => [2],
    ]);

    LayoutDeviation::create([
        'start' => '2026-03-16',
        'end' => '2026-03-16',
        'layout_id' => $deviationLayout->id,
    ]);

    $service = new LayoutService;
    $result = $service->getLayoutWithModelForDate('2026-03-16');

    expect($result['data'])->toBe([['deviation']]);
    expect($result['model']?->id)->toBe($deviationLayout->id);
});
