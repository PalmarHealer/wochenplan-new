<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('disabled')->default(false);
            $table->dateTime('date');
            $table->foreignId('color')->constrained('colors')->nullOnDelete();
            $table->foreignId('room')->constrained('rooms')->nullOnDelete();
            $table->foreignId('lesson_time')->constrained('times')->nullOnDelete();

            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('lesson_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('lesson_user');
    }
};
