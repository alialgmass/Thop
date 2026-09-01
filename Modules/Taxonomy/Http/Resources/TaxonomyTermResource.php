<?php

namespace Modules\Taxonomy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Taxonomy\Models\TaxonomyTerm;

/**
 * @mixin TaxonomyTerm
 */
class TaxonomyTermResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_filter([
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->localizedName(),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'hex' => $this->resource->getAttribute('hex'),
        ], fn ($value): bool => $value !== null);
    }
}
