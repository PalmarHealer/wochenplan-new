<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"tes","guard_name":"web","permissions":["view_color","view_role"]},{"name":"super_admin","guard_name":"web","permissions":[]}]';
        $directPermissions = '{"0":{"name":"view_absence","guard_name":"web"},"1":{"name":"view_any_absence","guard_name":"web"},"2":{"name":"create_absence","guard_name":"web"},"3":{"name":"update_absence","guard_name":"web"},"4":{"name":"restore_absence","guard_name":"web"},"5":{"name":"restore_any_absence","guard_name":"web"},"6":{"name":"replicate_absence","guard_name":"web"},"7":{"name":"reorder_absence","guard_name":"web"},"8":{"name":"delete_absence","guard_name":"web"},"9":{"name":"delete_any_absence","guard_name":"web"},"10":{"name":"force_delete_absence","guard_name":"web"},"11":{"name":"force_delete_any_absence","guard_name":"web"},"13":{"name":"view_any_color","guard_name":"web"},"14":{"name":"create_color","guard_name":"web"},"15":{"name":"update_color","guard_name":"web"},"16":{"name":"restore_color","guard_name":"web"},"17":{"name":"restore_any_color","guard_name":"web"},"18":{"name":"replicate_color","guard_name":"web"},"19":{"name":"reorder_color","guard_name":"web"},"20":{"name":"delete_color","guard_name":"web"},"21":{"name":"delete_any_color","guard_name":"web"},"22":{"name":"force_delete_color","guard_name":"web"},"23":{"name":"force_delete_any_color","guard_name":"web"},"24":{"name":"view_lesson","guard_name":"web"},"25":{"name":"view_any_lesson","guard_name":"web"},"26":{"name":"create_lesson","guard_name":"web"},"27":{"name":"update_lesson","guard_name":"web"},"28":{"name":"restore_lesson","guard_name":"web"},"29":{"name":"restore_any_lesson","guard_name":"web"},"30":{"name":"replicate_lesson","guard_name":"web"},"31":{"name":"reorder_lesson","guard_name":"web"},"32":{"name":"delete_lesson","guard_name":"web"},"33":{"name":"delete_any_lesson","guard_name":"web"},"34":{"name":"force_delete_lesson","guard_name":"web"},"35":{"name":"force_delete_any_lesson","guard_name":"web"},"37":{"name":"view_any_role","guard_name":"web"},"38":{"name":"create_role","guard_name":"web"},"39":{"name":"update_role","guard_name":"web"},"40":{"name":"delete_role","guard_name":"web"},"41":{"name":"delete_any_role","guard_name":"web"},"42":{"name":"view_room","guard_name":"web"},"43":{"name":"view_any_room","guard_name":"web"},"44":{"name":"create_room","guard_name":"web"},"45":{"name":"update_room","guard_name":"web"},"46":{"name":"restore_room","guard_name":"web"},"47":{"name":"restore_any_room","guard_name":"web"},"48":{"name":"replicate_room","guard_name":"web"},"49":{"name":"reorder_room","guard_name":"web"},"50":{"name":"delete_room","guard_name":"web"},"51":{"name":"delete_any_room","guard_name":"web"},"52":{"name":"force_delete_room","guard_name":"web"},"53":{"name":"force_delete_any_room","guard_name":"web"},"54":{"name":"view_time","guard_name":"web"},"55":{"name":"view_any_time","guard_name":"web"},"56":{"name":"create_time","guard_name":"web"},"57":{"name":"update_time","guard_name":"web"},"58":{"name":"restore_time","guard_name":"web"},"59":{"name":"restore_any_time","guard_name":"web"},"60":{"name":"replicate_time","guard_name":"web"},"61":{"name":"reorder_time","guard_name":"web"},"62":{"name":"delete_time","guard_name":"web"},"63":{"name":"delete_any_time","guard_name":"web"},"64":{"name":"force_delete_time","guard_name":"web"},"65":{"name":"force_delete_any_time","guard_name":"web"},"66":{"name":"view_user","guard_name":"web"},"67":{"name":"view_any_user","guard_name":"web"},"68":{"name":"create_user","guard_name":"web"},"69":{"name":"update_user","guard_name":"web"},"70":{"name":"restore_user","guard_name":"web"},"71":{"name":"restore_any_user","guard_name":"web"},"72":{"name":"replicate_user","guard_name":"web"},"73":{"name":"reorder_user","guard_name":"web"},"74":{"name":"delete_user","guard_name":"web"},"75":{"name":"delete_any_user","guard_name":"web"},"76":{"name":"force_delete_user","guard_name":"web"},"77":{"name":"force_delete_any_user","guard_name":"web"},"78":{"name":"page_MyProfilePage","guard_name":"web"},"79":{"name":"view_absence","guard_name":""},"80":{"name":"view_any_absence","guard_name":""},"81":{"name":"create_absence","guard_name":""},"82":{"name":"update_absence","guard_name":""},"83":{"name":"delete_absence","guard_name":""},"84":{"name":"delete_any_absence","guard_name":""},"86":{"name":"view_any_color","guard_name":""},"87":{"name":"create_color","guard_name":""},"88":{"name":"update_color","guard_name":""},"89":{"name":"delete_color","guard_name":""},"90":{"name":"delete_any_color","guard_name":""},"91":{"name":"view_lesson","guard_name":""},"92":{"name":"view_any_lesson","guard_name":""},"93":{"name":"create_lesson","guard_name":""},"94":{"name":"update_lesson","guard_name":""},"95":{"name":"delete_lesson","guard_name":""},"96":{"name":"delete_any_lesson","guard_name":""},"98":{"name":"view_any_role","guard_name":""},"99":{"name":"create_role","guard_name":""},"100":{"name":"update_role","guard_name":""},"101":{"name":"delete_role","guard_name":""},"102":{"name":"delete_any_role","guard_name":""},"103":{"name":"view_room","guard_name":""},"104":{"name":"view_any_room","guard_name":""},"105":{"name":"create_room","guard_name":""},"106":{"name":"update_room","guard_name":""},"107":{"name":"delete_room","guard_name":""},"108":{"name":"delete_any_room","guard_name":""},"109":{"name":"view_time","guard_name":""},"110":{"name":"view_any_time","guard_name":""},"111":{"name":"create_time","guard_name":""},"112":{"name":"update_time","guard_name":""},"113":{"name":"delete_time","guard_name":""},"114":{"name":"delete_any_time","guard_name":""},"115":{"name":"view_user","guard_name":""},"116":{"name":"view_any_user","guard_name":""},"117":{"name":"create_user","guard_name":""},"118":{"name":"update_user","guard_name":""},"119":{"name":"delete_user","guard_name":""},"120":{"name":"delete_any_user","guard_name":""},"121":{"name":"page_MyProfilePage","guard_name":""},"122":{"name":"publish_lesson","guard_name":"web"},"123":{"name":"view_layout","guard_name":"web"},"124":{"name":"create_layout","guard_name":"web"},"125":{"name":"update_layout","guard_name":"web"},"126":{"name":"delete_layout","guard_name":"web"},"127":{"name":"delete_any_layout","guard_name":"web"},"128":{"name":"page_Day","guard_name":"web"},"129":{"name":"viewAny_absence","guard_name":"web"},"130":{"name":"deleteAny_absence","guard_name":"web"},"131":{"name":"view_lesson::template","guard_name":"web"},"132":{"name":"view_any_lesson::template","guard_name":"web"},"133":{"name":"create_lesson::template","guard_name":"web"},"134":{"name":"update_lesson::template","guard_name":"web"},"135":{"name":"restore_lesson::template","guard_name":"web"},"136":{"name":"restore_any_lesson::template","guard_name":"web"},"137":{"name":"delete_lesson::template","guard_name":"web"},"138":{"name":"delete_any_lesson::template","guard_name":"web"},"139":{"name":"force_delete_lesson::template","guard_name":"web"},"140":{"name":"force_delete_any_lesson::template","guard_name":"web"}}';

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
