<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Search\Support\SearchNormalizer;

/**
 * Phase 4 (Search): a normalized searchable column on products, plus the
 * filter/sort indexes and the MySQL FULLTEXT index the Search module consumes.
 *
 * On MySQL the FULLTEXT index moves from (name_ar, name_en) to `search_text`
 * so free-text matching runs against normalized text. On SQLite (the test
 * driver) FULLTEXT is a guarded no-op — the Search service falls back to LIKE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->text('search_text')->nullable()->after('description');
            $table->index(['status', 'price']);
            $table->index(['status', 'created_at']);
        });

        $this->backfillSearchText();

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE products DROP INDEX fulltext_name');
        DB::statement('ALTER TABLE products ADD FULLTEXT fulltext_search_text (search_text)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE products DROP INDEX fulltext_search_text');
            DB::statement('ALTER TABLE products ADD FULLTEXT fulltext_name (name_ar, name_en)');
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['status', 'price']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn('search_text');
        });
    }

    private function backfillSearchText(): void
    {
        $normalizer = new SearchNormalizer;

        DB::table('products')->orderBy('id')->chunkById(200, function ($rows) use ($normalizer): void {
            foreach ($rows as $row) {
                DB::table('products')->where('id', $row->id)->update([
                    'search_text' => $normalizer->normalizeParts([$row->name_ar, $row->name_en, $row->description]),
                ]);
            }
        });
    }
};
