<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user, per-(category, channel) notification preference (§10.8,
 * US-NOT-02). A missing row means "use the category default" — operational
 * categories default enabled, `marketing` defaults disabled (US-NOT-04) — so
 * the table only ever holds explicit overrides, never the full grid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->string('channel');
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(['user_id', 'category', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
