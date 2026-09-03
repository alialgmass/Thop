<?php

namespace Modules\Search\Observers;

use Modules\Catalog\Models\Product;
use Modules\Search\Support\SearchNormalizer;

/**
 * Maintains {@see Product::$search_text} — the normalized concatenation of the
 * bilingual name and description that free-text search matches against. Runs on
 * every save so an edited name stays findable (US-SRC-01).
 */
class ProductSearchIndexer
{
    public function __construct(private SearchNormalizer $normalizer) {}

    public function saving(Product $product): void
    {
        if (! $product->isDirty(['name_ar', 'name_en', 'description']) && $product->exists) {
            return;
        }

        $product->search_text = $this->normalizer->normalizeParts([
            $product->name_ar,
            $product->name_en,
            $product->description,
        ]);
    }
}
