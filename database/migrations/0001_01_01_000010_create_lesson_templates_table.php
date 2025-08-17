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
        Schema::create('lesson_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('disabled')->default(false);
            $table->tinyInteger('weekday');
            $table->foreignId('color')->nullable()->constrained('colors')->nullOnDelete();
            $table->foreignId('room')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('lesson_time')->nullable()->constrained('times')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();

            $table->timestamps();
        });


        Schema::create('lesson_template_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_templates');
        Schema::dropIfExists('lesson_template_user');
    }
};
