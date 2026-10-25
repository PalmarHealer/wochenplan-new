<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    public const MIN_PASSWORD_LENGTH = 12;

    private const DEFAULT_PASSWORDS = ['admin', 'password', 'changeme', 'secret', '12345678', '123456789012'];

    public static function isWeakPassword(string $password): bool
    {
        return mb_strlen($password) < self::MIN_PASSWORD_LENGTH
            || in_array(mb_strtolower($password), self::DEFAULT_PASSWORDS, true);
    }

    public function run(): void
    {
        // Read credentials from .env
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD not set in .env; skipping AdminUserSeeder.');

            return;
        }

        // Never create a super admin with a guessable password on a live instance.
        if (app()->isProduction() && self::isWeakPassword($password)) {
            $this->command?->warn('ADMIN_PASSWORD is a default or shorter than '.self::MIN_PASSWORD_LENGTH.' characters; skipping AdminUserSeeder in production.');

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

        // If user existed already, align name/display_name and keep the password in sync with the env,
        // so it can be rotated by changing ADMIN_PASSWORD.
        $updates = [];
        if (! Hash::check($password, $user->password)) {
            $updates['password'] = $password;
        }
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

        // Ensure the role exists with all permissions (also seeded on its own on every start).
        (new SuperAdminRoleSeeder)->run();

        if (! $user->hasRole(SuperAdminRoleSeeder::ROLE)) {
            $user->assignRole(SuperAdminRoleSeeder::ROLE);
        }
    }
}
