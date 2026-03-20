<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate([
        'name' => 'api.access',
        'guard_name' => 'web',
    ]);
});

it('allows api ping with valid token permission and recent login', function () {
    $user = User::factory()->create([
        'last_login_at' => now()->subDays(10),
    ]);
    $user->givePermissionTo('api.access');

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/ping')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'user_id' => $user->id,
        ]);
});

it('rejects api ping when permission is missing', function () {
    $user = User::factory()->create([
        'last_login_at' => now()->subDays(10),
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/ping')
        ->assertForbidden()
        ->assertJson([
            'message' => 'You are not authorized to access the API.',
        ]);
});

it('rejects api ping when last login is stale', function () {
    $user = User::factory()->create([
        'last_login_at' => now()->subMonths(7),
    ]);
    $user->givePermissionTo('api.access');

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/ping')
        ->assertForbidden()
        ->assertJson([
            'message' => 'API access requires a successful web login within the last 6 months.',
        ]);
});

it('rejects api ping with revoked token and accepts new rotated token', function () {
    $user = User::factory()->create([
        'last_login_at' => now(),
    ]);
    $user->givePermissionTo('api.access');

    $oldToken = $user->createToken('old')->plainTextToken;
    $user->tokens()->delete();
    $newToken = $user->createToken('new')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$oldToken)
        ->getJson('/api/v1/ping')
        ->assertUnauthorized();

    $this->withHeader('Authorization', 'Bearer '.$newToken)
        ->getJson('/api/v1/ping')
        ->assertOk();
});

it('exposes unauthenticated api index endpoint', function () {
    $this->getJson('/api')
        ->assertOk()
        ->assertJsonPath('endpoints.0.path', '/api');
});

it('applies the same auth rules to mcp endpoint', function () {
    $user = User::factory()->create([
        'last_login_at' => now(),
    ]);
    $user->givePermissionTo('api.access');

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/mcp')
        ->assertOk()
        ->assertJsonPath('name', config('app.name').' MCP');
});
