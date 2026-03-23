<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate([
            'name' => 'auto_approve_ai_actions',
            'guard_name' => 'web',
        ]);
    }

    public function down(): void
    {
        Permission::where('name', 'auto_approve_ai_actions')->delete();
    }
};
