<?php

namespace Modules\Taxonomy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Taxonomy\Models\TaxonomyTerm;

/**
 * Admin-facing term shape (US-ADM-03) — unlike {@see TaxonomyTermResource},
 * this always includes `is_active` so a deactivated term is still visible
 * (and distinguishable) to the admin managing it.
 *
 * @mixin TaxonomyTerm
 */
class AdminTaxonomyTermResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_filter([
            'id' => $this->id,
            'slug' => $this->slug,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'is_active' => $this->is_active,
            'hex' => $this->resource->getAttribute('hex'),
        ], fn ($value): bool => $value !== null);
    }
}
