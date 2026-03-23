<?php

use App\Models\Lunch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create([
        'name' => 'update_layout',
        'guard_name' => 'web',
    ]);
});

it('requires authentication for clearing lunch', function () {
    $response = $this->postJson(route('lunch.clear'), [
        'date' => '2026-03-20',
    ]);

    $response->assertUnauthorized();
});

it('returns forbidden when user has no update_layout permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('lunch.clear'), [
        'date' => '2026-03-20',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'success' => false,
    ]);
});

it('validates date format when authorized user clears lunch', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('update_layout');

    $response = $this->actingAs($user)->postJson(route('lunch.clear'), [
        'date' => '20-03-2026',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);
});

it('clears existing lunch entry for authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('update_layout');

    Lunch::create([
        'date' => '2026-03-20',
        'lunch' => 'Noodles',
    ]);

    $response = $this->actingAs($user)->postJson(route('lunch.clear'), [
        'date' => '2026-03-20',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
    expect(Lunch::whereDate('date', '2026-03-20')->exists())->toBeFalse();
});

it('returns success with no-op message when no lunch existed', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('update_layout');

    $response = $this->actingAs($user)->postJson(route('lunch.clear'), [
        'date' => '2026-03-23',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Kein Mittagessen für 2026-03-23 gefunden',
    ]);
});
