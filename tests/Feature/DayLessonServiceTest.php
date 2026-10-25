<?php

use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\Room;
use App\Models\Time;
use App\Services\DayLessonService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->monday = Carbon::parse('2026-10-26');
    $this->room = Room::create(['name' => 'Aula']);
    $this->otherRoom = Room::create(['name' => 'Werkstatt']);
    $this->time = Time::create(['name' => '9:00']);

    $this->template = LessonTemplate::create([
        'name' => 'Template', 'weekday' => 1, 'room' => $this->room->id, 'lesson_time' => $this->time->id,
    ]);
    $this->template->forceFill(['created_at' => '2026-01-01'])->save();
});

function slotKey($lesson): string
{
    return $lesson->room.'-'.$lesson->lesson_time;
}

it('returns the weekday template keyed by its slot', function () {
    $lessons = app(DayLessonService::class)->lessonsForDate($this->monday);

    expect($lessons)->toHaveCount(1)
        ->and($lessons->get(slotKey($this->template))->name)->toBe('Template');
});

it('lets a concrete lesson in the same slot replace the template', function () {
    Lesson::create([
        'name' => 'Override', 'date' => $this->monday, 'room' => $this->room->id, 'lesson_time' => $this->time->id,
    ]);

    $lessons = app(DayLessonService::class)->lessonsForDate($this->monday);

    expect($lessons)->toHaveCount(1)
        ->and($lessons->first()->name)->toBe('Override');
});

it('drops a template that a lesson overrides via parent_id in another slot', function () {
    Lesson::create([
        'name' => 'Moved', 'parent_id' => $this->template->id, 'date' => $this->monday,
        'room' => $this->otherRoom->id, 'lesson_time' => $this->time->id,
    ]);

    $lessons = app(DayLessonService::class)->lessonsForDate($this->monday);

    expect($lessons->pluck('name')->all())->toBe(['Moved']);
});

it('ignores templates created after or deleted before the date', function () {
    $this->template->forceFill(['created_at' => '2026-10-27'])->save();
    expect(app(DayLessonService::class)->lessonsForDate($this->monday))->toBeEmpty();

    $this->template->forceFill(['created_at' => '2026-01-01', 'deleted_at' => '2026-10-20'])->save();
    expect(app(DayLessonService::class)->lessonsForDate($this->monday))->toBeEmpty();
});
