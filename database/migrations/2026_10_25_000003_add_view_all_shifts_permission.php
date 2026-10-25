<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Without this permission the shift plan only shows the own shifts. Everyone who
     * can open the shift plan today keeps seeing all of them.
     */
    public function up(): void
    {
        $permission = Permission::findOrCreate('view_all_shifts', 'web');

        Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'page_ShiftPlan'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'view_all_shifts')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
