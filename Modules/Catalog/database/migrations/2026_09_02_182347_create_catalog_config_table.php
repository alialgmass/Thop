<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-backed overrides for catalog gating flags (BR-SEL-02). The admin management
 * UI for these toggles is Phase 9; this table lets the value change without a
 * deploy. The read path (CatalogConfig) checks the override first and falls back
 * to config('catalog.*').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_config', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_config');
    }
};
