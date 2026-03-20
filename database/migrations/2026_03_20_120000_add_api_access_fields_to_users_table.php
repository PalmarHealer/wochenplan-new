<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('password');
            $table->timestamp('api_token_last_rotated_at')->nullable()->after('last_login_at');
            $table->string('api_token_last_8', 8)->nullable()->after('api_token_last_rotated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_at',
                'api_token_last_rotated_at',
                'api_token_last_8',
            ]);
        });
    }
};
