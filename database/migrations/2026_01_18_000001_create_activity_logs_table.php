<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('timestamp')->useCurrent();
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->string('user_agent')->nullable();
            $table->string('action', 100)->index(); // login, logout, create, update, delete, view, click, etc.
            $table->string('action_category', 50)->index(); // auth, data, navigation, interaction
            $table->string('resource_type', 100)->nullable()->index(); // Model class name or page name
            $table->string('resource_id', 100)->nullable(); // ID of the affected resource
            $table->string('resource_label')->nullable(); // Human-readable label (e.g., "User: John Doe")
            $table->json('content')->nullable(); // Before/after data, additional context
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->integer('response_code')->nullable();
            $table->string('session_id', 100)->nullable()->index();
            $table->boolean('is_suspicious')->default(false)->index();
            $table->text('notes')->nullable();

            // Indexes for common queries
            $table->index(['user_id', 'timestamp']);
            $table->index(['action', 'timestamp']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
