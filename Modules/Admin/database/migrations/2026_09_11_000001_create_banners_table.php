<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage banners for the separate marketplace client — this repo has no
 * homepage of its own (Phase 9 · T6, issue #35).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('image_disk');
            $table->string('image_path');
            $table->string('link_url')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
