<?php

use App\Filament\Pages\Day;
use App\Models\Layout;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\Room;
use App\Models\Time;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->room = Room::create(['name' => 'Aula']);
    $this->time = Time::create(['name' => '10:15 Angebot 1']);
    Layout::create([
        'name' => 'Montag',
        'layout' => json_encode([[['room' => $this->room->id, 'time' => $this->time->id]]]),
        'weekdays' => [1],
    ]);

    foreach (['create_lesson', 'create_lesson::template', 'view_any_lesson', 'view_any_lesson::template'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $this->user = User::factory()->create();
});

function dayPage()
{
    return Livewire::actingAs(test()->user)
        ->withQueryParams(['date' => '26.10.2026'])
        ->test(Day::class);
}

it('creates a lesson for this day only from an empty slot', function () {
    $this->user->givePermissionTo(['create_lesson', 'view_any_lesson']);

    dayPage()
        ->assertSee('Klicken, um hier ein Angebot anzulegen')
        ->callAction('createLesson', ['name' => 'Werken', 'assignedUsers' => [$this->user->id]],
            ['room' => $this->room->id, 'time' => $this->time->id, 'recurring' => false])
        ->assertHasNoActionErrors()
        ->assertSee('Werken');

    $lesson = Lesson::sole();
    expect(Carbon::parse($lesson->date)->toDateString())->toBe('2026-10-26')
        ->and($lesson->room)->toBe($this->room->id)
        ->and($lesson->lesson_time)->toBe($this->time->id)
        ->and($lesson->assignedUsers->pluck('id')->all())->toBe([$this->user->id]);
});

it('creates a recurring lesson for this weekday from an empty slot', function () {
    $this->user->givePermissionTo(['create_lesson::template']);

    dayPage()
        ->callAction('createLesson', ['name' => 'Chor'],
            ['room' => $this->room->id, 'time' => $this->time->id, 'recurring' => true])
        ->assertHasNoActionErrors();

    expect(LessonTemplate::sole())
        ->name->toBe('Chor')
        ->weekday->toBe(1);
});

it('does not offer or allow creating without permission', function () {
    dayPage()
        ->assertDontSee('Klicken, um hier ein Angebot anzulegen')
        ->assertActionHidden('createLesson');
});
