<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Search\Support\SearchNormalizer;

/**
 * Phase 4 (Search): supplier search (US-SRC-07) over business_accounts by
 * governorate / verification status / specialty, with optional free text over
 * company name and activity. A normalized column keeps matching consistent
 * with product search; the MySQL FULLTEXT index is a no-op on SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_accounts', function (Blueprint $table): void {
            $table->text('search_text')->nullable()->after('activity');
            $table->index('company_name');
        });

        $this->backfill();

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE business_accounts ADD FULLTEXT fulltext_supplier_search (search_text)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE business_accounts DROP INDEX fulltext_supplier_search');
        }

        Schema::table('business_accounts', function (Blueprint $table): void {
            $table->dropIndex(['company_name']);
            $table->dropColumn('search_text');
        });
    }

    private function backfill(): void
    {
        $normalizer = new SearchNormalizer;

        DB::table('business_accounts')->orderBy('id')->chunkById(200, function ($rows) use ($normalizer): void {
            foreach ($rows as $row) {
                DB::table('business_accounts')->where('id', $row->id)->update([
                    'search_text' => $normalizer->normalizeParts([$row->company_name, $row->activity]),
                ]);
            }
        });
    }
};
