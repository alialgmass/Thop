<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Public-facing product card for search results and per-supplier catalogs
 * (US-SRC-01..04). Exposes only buyer-facing fields plus the `featured` flag
 * (BR-SRC-01) and the owning supplier's identity for the results list. Does
 * NOT expose seller-internal metadata (created_by, rejection_reason, …).
 */
class ProductCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'width_cm' => $this->width_cm,
            'weight_gsm' => $this->weight_gsm,
            'price' => $this->price,
            'price_on_contact' => $this->price_on_contact,
            'currency' => $this->currency,
            'unit' => $this->unit,
            'moq' => $this->moq,
            'quantity_available' => $this->quantity_available,
            'featured' => (bool) ($this->featured ?? false),
            'fabric_type' => $this->whenLoaded('fabricType', fn () => $this->fabricType?->localizedName()),
            'material' => $this->whenLoaded('material', fn () => $this->material?->localizedName()),
            'governorate' => $this->whenLoaded('governorate', fn () => $this->governorate?->localizedName()),
            'colors' => $this->whenLoaded('colors', fn () => $this->colors->pluck('id', 'name_en')),
            'primary_image' => $this->whenLoaded('media', fn () => $this->primaryImageUrl()),
            'supplier' => $this->whenLoaded('businessAccount', fn () => [
                'id' => $this->businessAccount->id,
                'company_name' => $this->businessAccount->company_name,
                'verified' => $this->businessAccount->isVerified(),
            ]),
        ];
    }

    private function primaryImageUrl(): ?string
    {
        $image = $this->media->firstWhere('type', 'image');

        return $image === null ? null : Storage::disk($image->disk)->url($image->path);
    }
}
