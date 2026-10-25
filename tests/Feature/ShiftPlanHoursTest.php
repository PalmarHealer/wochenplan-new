<?php

use App\Filament\Pages\ShiftPlan;
use App\Models\Layout;
use App\Models\Room;
use App\Models\Time;
use App\Services\DayLessonService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function entry(?int $start, ?int $end, bool $disabled = false): array
{
    return ['start' => $start, 'end' => $end, 'disabled' => $disabled];
}

it('parses times from the start of a name', function () {
    expect(Time::toMinutes('10:15 Angebot 1'))->toBe(615)
        ->and(Time::toMinutes('8.00 Ankommen'))->toBe(480)
        ->and(Time::toMinutes('Den ganzen Tag gültig'))->toBeNull()
        ->and(Time::toMinutes('25:00'))->toBeNull()
        ->and(Time::formatMinutes(615))->toBe('10:15');
});

it('sums the covered time of a day and counts overlaps once', function () {
    $presence = ShiftPlan::presence([
        entry(600, 660),            // 10:00–11:00
        entry(630, 690),            // 10:30–11:30, overlaps
        entry(780, 825),            // 13:00–13:45
        entry(900, 960, true),      // cancelled, ignored
        entry(null, null),          // no time, ignored
    ]);

    expect($presence)->toBe(['from' => 600, 'until' => 825, 'minutes' => 135]);
});

it('has no presence without lessons that take place', function () {
    expect(ShiftPlan::presence([entry(600, 660, true)]))
        ->toBe(['from' => null, 'until' => null, 'minutes' => 0]);
});

it('ends a time at the next start in the layout of the day unless an end is set', function () {
    $room = Room::create(['name' => 'Aula']);
    $first = Time::create(['name' => '10:15 Angebot 1']);
    $second = Time::create(['name' => '11:00 Angebot 2']);
    $last = Time::create(['name' => '15:00 Check-Out']);
    $fixed = Time::create(['name' => 'Spät', 'starts_at' => '16:00', 'ends_at' => '17:30']);
    $unused = Time::create(['name' => '10:30 Anderes Layout']);

    $cell = fn (Time $time) => ['room' => $room->id, 'time' => $time->id];
    Layout::create([
        'name' => 'Montag',
        'layout' => json_encode([[$cell($second)], [$cell($first)], [$cell($last)], [$cell($fixed)]]),
        'weekdays' => [1],
    ]);

    $ranges = app(DayLessonService::class)->timeRangesForDate(Carbon::parse('2026-10-26'));

    expect($ranges[$first->id])->toBe(['start' => 615, 'end' => 660])
        ->and($ranges[$second->id])->toBe(['start' => 660, 'end' => 900])
        ->and($ranges[$last->id])->toBe(['start' => 900, 'end' => 960])
        ->and($ranges[$fixed->id])->toBe(['start' => 960, 'end' => 1050])
        ->and($ranges)->not->toHaveKey($unused->id);
});
