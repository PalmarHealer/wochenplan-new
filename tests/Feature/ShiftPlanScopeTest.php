<?php

use App\Filament\Pages\ShiftPlan;
use App\Models\Layout;
use App\Models\LessonTemplate;
use App\Models\Room;
use App\Models\Time;
use App\Models\User;
use Database\Seeders\SuperAdminRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $room = Room::create(['name' => 'Aula']);
    $time = Time::create(['name' => '10:15 Angebot 1']);
    Layout::create([
        'name' => 'Montag',
        'layout' => json_encode([[['room' => $room->id, 'time' => $time->id]]]),
        'weekdays' => [1],
    ]);

    $this->me = User::factory()->create(['display_name' => 'Ich']);
    $this->other = User::factory()->create(['display_name' => 'Andere']);

    $template = LessonTemplate::create([
        'name' => 'Werken', 'weekday' => 1, 'room' => $room->id, 'lesson_time' => $time->id,
    ]);
    $template->forceFill(['created_at' => '2026-01-01'])->save();
    $template->assignedUsers()->sync([$this->me->id, $this->other->id]);

    Permission::findOrCreate('view_all_shifts', 'web');
});

function shiftPlanNames(?string $scope = null): array
{
    $page = new ShiftPlan;
    $page->week = '26.10.2026';
    $page->scope = $scope;
    $page->mount();

    return array_column($page->rows, 'name');
}

it('shows only the own shifts without view_all_shifts', function () {
    $this->actingAs($this->me);

    expect(shiftPlanNames())->toBe(['Ich'])
        ->and(shiftPlanNames('alle'))->toBe(['Ich']);
});

it('shows everyone with view_all_shifts and can switch to the own shifts', function () {
    $this->me->givePermissionTo('view_all_shifts');
    $this->actingAs($this->me);

    expect(shiftPlanNames())->toBe(['Andere', 'Ich'])
        ->and(shiftPlanNames('meins'))->toBe(['Ich']);
});

it('gives super_admin every permission, including later added ones', function () {
    Role::findOrCreate('super_admin', 'web')->syncPermissions([]);
    Permission::findOrCreate('page_Something', 'web');

    (new SuperAdminRoleSeeder)->run();

    expect(Role::findByName('super_admin', 'web')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(Permission::pluck('name')->sort()->values()->all());
});

it('renders the scope toggle only for users who can see everyone', function () {
    Permission::findOrCreate('page_ShiftPlan', 'web');
    $this->me->givePermissionTo('page_ShiftPlan');
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));

    Livewire\Livewire::actingAs($this->me)
        ->withQueryParams(['woche' => '26.10.2026'])
        ->test(ShiftPlan::class)
        ->assertDontSee('Alle')
        ->assertSee('10:15–')
        ->assertDontSee('Andere');

    $this->me->givePermissionTo('view_all_shifts');

    Livewire\Livewire::actingAs($this->me->fresh())
        ->withQueryParams(['woche' => '26.10.2026'])
        ->test(ShiftPlan::class)
        ->assertSee(['Meins', 'Alle', 'Andere', 'h / Woche'])
        ->call('setScope', 'meins')
        ->assertDontSee('Andere')
        ->assertSet('scope', 'meins');
});
