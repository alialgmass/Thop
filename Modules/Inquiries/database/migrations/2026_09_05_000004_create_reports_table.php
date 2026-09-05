<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A durable record that either party flagged an inquiry as abusive
 * (US-INQ-09, INQ-FR-09). Scoped to `inquiries` for now via the morph map
 * (`ReportsServiceProvider`); ready for `messages` once Phase 7 lands. The
 * Admin dispute/ticket-queue UI that reads these rows is Phase 9 (US-ADM-08)
 * — this table only guarantees the record exists for that queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
