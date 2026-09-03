<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zero-result search terms (US-SRC-11, SRC-FR-11). Only searches that returned
 * nothing are written here; this is an unmet-demand signal for the Phase 9
 * admin liquidity dashboard, not a full search-analytics log. No PII beyond an
 * optional user id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('term');
            $table->string('normalized_term');
            $table->unsignedInteger('result_count')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('context')->default('product');
            $table->timestamp('created_at')->nullable();

            $table->index(['context', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
