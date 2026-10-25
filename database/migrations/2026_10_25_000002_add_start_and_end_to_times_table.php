<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional overrides. Without them the start is read from the name ("10:15 Angebot 1")
     * and the end is the start of the next time in the layout of the day.
     */
    public function up(): void
    {
        Schema::table('times', function (Blueprint $table) {
            $table->string('starts_at', 5)->nullable()->after('name');
            $table->string('ends_at', 5)->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('times', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
