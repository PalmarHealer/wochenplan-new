<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gives super_admin every permission that exists, on every start. Runs after the
 * ShieldSeeder, whose fixed list does not know permissions added by later migrations.
 */
class SuperAdminRoleSeeder extends Seeder
{
    public const ROLE = 'super_admin';

    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $role = Utils::getRoleModel()::firstOrCreate([
            'name' => self::ROLE,
            'guard_name' => $guard,
        ]);

        $role->syncPermissions(Utils::getPermissionModel()::query()->where('guard_name', $guard)->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
