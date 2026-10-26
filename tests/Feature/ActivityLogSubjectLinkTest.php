<?php

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\LessonResource;
use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Time;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    foreach (['update_lesson', 'view_any_lesson'] as $name) {
        Permission::findOrCreate($name, 'web');
    }
    $this->user = User::factory()->create();
    $this->lesson = Lesson::create([
        'name' => '<p>Acker</p>',
        'date' => '2026-10-26',
        'room' => Room::create(['name' => 'Aula'])->id,
        'lesson_time' => Time::create(['name' => '9:00'])->id,
    ]);
});

function logFor(string $type, string $id): ActivityLog
{
    return new ActivityLog(['resource_type' => $type, 'resource_id' => $id, 'resource_label' => 'Lesson: <p>Acker</p>']);
}

it('links to the edit page of the logged record', function () {
    $this->user->givePermissionTo(['update_lesson', 'view_any_lesson']);
    $this->actingAs($this->user);

    expect(ActivityLogResource::subjectUrl(logFor(Lesson::class, (string) $this->lesson->id)))
        ->toBe(LessonResource::getUrl('edit', ['record' => $this->lesson]));
});

it('does not link without permission or when the record is gone', function () {
    $this->actingAs($this->user);
    expect(ActivityLogResource::subjectUrl(logFor(Lesson::class, (string) $this->lesson->id)))->toBeNull();

    $this->user->givePermissionTo(['update_lesson', 'view_any_lesson']);
    expect(ActivityLogResource::subjectUrl(logFor(Lesson::class, '999999')))->toBeNull()
        ->and(ActivityLogResource::subjectUrl(logFor('login_attempt', '1')))->toBeNull();
});

it('shows labels as plain text, never as markup', function () {
    expect(ActivityLogResource::plainLabel('Lesson: <p><strong>Acker</strong> &amp; Garten</p>'))->toBe('Acker & Garten')
        ->and(ActivityLogResource::plainLabel('Email: "<a href=/x style=position:fixed>"@x.de'))->toBe('""@x.de');
});
