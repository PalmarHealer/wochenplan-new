<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{
            "name": "super_admin",
            "guard_name": "web",
            "permissions": [
                "view_absence",
                "view_any_absence",
                "create_absence",
                "update_absence",
                "delete_absence",
                "delete_any_absence",
                "view_color",
                "create_color",
                "update_color",
                "delete_color",
                "delete_any_color",
                "view_day::pdf",
                "view_any_day::pdf",
                "create_day::pdf",
                "delete_day::pdf",
                "delete_any_day::pdf",
                "view_layout",
                "create_layout",
                "update_layout",
                "delete_layout",
                "delete_any_layout",
                "view_layout::deviation",
                "create_layout::deviation",
                "update_layout::deviation",
                "delete_layout::deviation",
                "delete_any_layout::deviation",
                "view_lesson",
                "view_any_lesson",
                "create_lesson",
                "update_lesson",
                "delete_lesson",
                "delete_any_lesson",
                "view_lesson::template",
                "view_any_lesson::template",
                "create_lesson::template",
                "update_lesson::template",
                "restore_lesson::template",
                "restore_any_lesson::template",
                "delete_lesson::template",
                "delete_any_lesson::template",
                "force_delete_lesson::template",
                "force_delete_any_lesson::template",
                "view_role",
                "view_any_role",
                "create_role",
                "update_role",
                "delete_role",
                "delete_any_role",
                "view_room",
                "create_room",
                "update_room",
                "delete_room",
                "delete_any_room",
                "view_time",
                "create_time",
                "update_time",
                "delete_time",
                "delete_any_time",
                "view_user",
                "create_user",
                "update_user",
                "delete_user",
                "delete_any_user",
                "page_FAQ"
            ]
        }]';

        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
