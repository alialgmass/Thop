<?php

namespace Modules\Search\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Supplier search result card (US-SRC-07): company identity, location,
 * specialty, verified badge and the featured flag (BR-SRC-01).
 */
class SupplierCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'activity' => $this->activity,
            'governorate' => $this->whenLoaded('governorate', fn () => $this->governorate?->localizedName()),
            'verified' => $this->isVerified(),
            'featured' => (bool) ($this->featured ?? false),
        ];
    }
}
