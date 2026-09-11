<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-curated featured placements (US-SRC-10, BR-SRC-01, Phase 9 · T5,
 * issue #34) — independent of a supplier's plan tier. `featurable_type` uses
 * the same non-enforcing morph-map alias convention as
 * `Modules\Favorites\Enums\FavoritableType` ('product' | 'supplier'), reused
 * directly rather than re-declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_placements', function (Blueprint $table): void {
            $table->id();
            $table->string('featurable_type');
            $table->unsignedBigInteger('featurable_id');
            $table->string('slot');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Unique per (item, slot) — an item may hold more than one slot
            // at once, but not the same slot twice. Re-featuring after
            // removal is fine: removal is a hard delete, not a soft one.
            $table->unique(['featurable_type', 'featurable_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_placements');
    }
};
