<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\PermissionRegistrar;
use BezhanSalleh\FilamentShield\Support\Utils;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Read credentials from .env
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD not set in .env; skipping AdminUserSeeder.');
            return;
        }

        // Ensure permissions cache is cleared before making changes
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create or get the user. Name and display_name should be the email.
        $user = User::firstOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $email,
                'display_name' => $email,
                // User model hashes password via cast
                'password' => $password,
            ]
        );

        // If user existed already, make sure the name/display_name are aligned and update password if you want to enforce it
        $updates = [];
        if ($user->name !== $email) {
            $updates['name'] = $email;
        }
        if (($user->display_name ?? null) !== $email) {
            $updates['display_name'] = $email;
        }
        if (! empty($updates)) {
            $user->fill($updates);
            $user->save();
        }

        // Prepare Role and Permission models via Filament Shield Utils for compatibility
        /** @var class-string<Role> $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $guard = config('auth.defaults.guard', 'web');

        // Create or get the admin role
        $adminRole = $roleModel::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => $guard,
        ]);

        // Give admin all existing permissions for the guard
        $allPermissions = $permissionModel::query()
            ->where('guard_name', $guard)
            ->get();

        if ($allPermissions->isNotEmpty()) {
            $adminRole->syncPermissions($allPermissions);
        }

        // Assign role to the user
        if (! $user->hasRole($adminRole)) {
            $user->assignRole($adminRole);
        }
    }
}
