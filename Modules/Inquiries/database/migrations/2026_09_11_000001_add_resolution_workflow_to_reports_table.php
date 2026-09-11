<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin dispute-queue resolution workflow on the existing `reports` table
 * (US-ADM-08, Phase 9 · T9, issue #38). Every existing row defaults to
 * `open` via the column default — no data migration needed. `updated_at` is
 * added here too: Report previously disabled it (`UPDATED_AT = null`,
 * matching its immutable-until-now shape) since nothing ever updated a
 * report row; resolving one now does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('status')->default('open')->after('reason');
            $table->foreignId('resolved_by')->nullable()->after('status')->constrained('users');
            $table->text('resolution_note')->nullable()->after('resolved_by');
            $table->timestamp('resolved_at')->nullable()->after('resolution_note');
            $table->timestamp('updated_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['status', 'resolution_note', 'resolved_at', 'updated_at']);
        });
    }
};
