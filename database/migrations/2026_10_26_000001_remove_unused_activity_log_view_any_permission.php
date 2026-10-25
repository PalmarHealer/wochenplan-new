<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Never checked (the policy only uses view_activity::log) and therefore listed
     * as a stray custom permission in the role editor.
     */
    public function up(): void
    {
        Permission::where('name', 'view_any_activity::log')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::findOrCreate('view_any_activity::log', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
