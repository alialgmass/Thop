<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Taxonomy\Database\Factories\UnitFactory;

/**
 * Known gap (flagged, not fixed, in Phase 9 · T3 per user decision): unlike
 * the other three managed term types, `Product.unit` does NOT reference this
 * table — it's a hardcoded `enum('per_meter', 'per_kg')` column. Managing
 * `Unit` rows here (create/edit/deactivate) has no effect on product
 * validation, search, or any other consumer until that column is migrated to
 * a real `unit_id` FK, which is a separate, larger change.
 */
class Unit extends TaxonomyTerm
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected static function newFactory(): UnitFactory
    {
        return UnitFactory::new();
    }
}
