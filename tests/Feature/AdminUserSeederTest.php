<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function setAdminEnv(string $email, string $password): void
{
    foreach (['ADMIN_EMAIL' => $email, 'ADMIN_PASSWORD' => $password] as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

afterEach(function () {
    unset($_ENV['ADMIN_EMAIL'], $_SERVER['ADMIN_EMAIL'], $_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);
});

it('creates the admin as super admin', function () {
    setAdminEnv('admin@example.test', 'first-password-123');

    $this->seed(AdminUserSeeder::class);

    $user = User::where('email', 'admin@example.test')->firstOrFail();
    expect($user->hasRole('super_admin'))->toBeTrue()
        ->and(Hash::check('first-password-123', $user->password))->toBeTrue();
});

it('syncs the password of an existing admin with the env', function () {
    setAdminEnv('admin@example.test', 'first-password-123');
    $this->seed(AdminUserSeeder::class);

    setAdminEnv('admin@example.test', 'rotated-password-456');
    $this->seed(AdminUserSeeder::class);

    $user = User::where('email', 'admin@example.test')->firstOrFail();
    expect(Hash::check('rotated-password-456', $user->password))->toBeTrue();
});

it('refuses weak passwords in production', function (string $password) {
    app()['env'] = 'production';
    setAdminEnv('admin@example.test', $password);

    // Run directly: db:seed would ask for confirmation in production.
    (new AdminUserSeeder)->run();

    expect(User::where('email', 'admin@example.test')->exists())->toBeFalse();
})->with(['admin', 'short-pw', 'PASSWORD']);

it('allows short passwords outside production', function () {
    setAdminEnv('admin@example.test', 'admin');

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@example.test')->exists())->toBeTrue();
});
