<?php

namespace Modules\Catalog\Support;

use Modules\Catalog\Models\CatalogConfig;

/**
 * Read path for the admin-review gating flags (BR-SEL-02). An admin can flip a
 * flag without a deploy through the catalog_config override table (the
 * {@see CatalogConfig} model); when no override exists the value falls back to
 * config('catalog.*'). The write path (and its admin UI) is Phase 9.
 */
class CatalogGating
{
    /**
     * Resolve a gating flag, preferring a DB override over the config default.
     */
    public static function bool(string $key, bool $default): bool
    {
        $override = CatalogConfig::query()
            ->where('key', 'catalog.'.$key)
            ->value('value');

        if ($override !== null) {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config("catalog.{$key}", $default);
    }

    /**
     * Whether a newly created product must pass through pending_review.
     */
    public static function requiresReviewOnCreate(): bool
    {
        return self::bool('review_create', true);
    }

    /**
     * Whether a material edit should send a published product back to review.
     */
    public static function requiresReviewOnEdit(): bool
    {
        return self::bool('review_edit', false);
    }
}
