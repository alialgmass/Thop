<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FULLTEXT index on the bilingual product name (Phase 4 Search consumes it).
 * MySQL supports FULLTEXT directly; SQLite (the test driver) has no FULLTEXT
 * concept, so this is a guarded no-op there. Tests keep running on SQLite per
 * the Phase 1/2 "migrations MySQL-compatible, tests on SQLite" note.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE products ADD FULLTEXT fulltext_name (name_ar, name_en)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('products', function ($table): void {
            $table->dropIndex('fulltext_name');
        });
    }
};
