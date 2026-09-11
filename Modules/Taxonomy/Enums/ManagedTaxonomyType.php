<?php

namespace Modules\Taxonomy\Enums;

use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Material;
use Modules\Taxonomy\Models\TaxonomyTerm;
use Modules\Taxonomy\Models\Unit;

/**
 * The four `TaxonomyTerm` subclasses an admin manages (US-ADM-03, Phase 9 · T3).
 * `governorates` is deliberately excluded — it isn't part of this ticket's
 * scope and is managed elsewhere. The route-segment value is the single
 * source of truth for the `{type}` URL parameter in
 * `/api/v1/admin/taxonomy/{type}`.
 */
enum ManagedTaxonomyType: string
{
    case FabricTypes = 'fabric-types';
    case Materials = 'materials';
    case Colors = 'colors';
    case Units = 'units';

    /**
     * @return class-string<TaxonomyTerm>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::FabricTypes => FabricType::class,
            self::Materials => Material::class,
            self::Colors => Color::class,
            self::Units => Unit::class,
        };
    }
}
