<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\Product;

/**
 * The full product resource for the seller's own catalog view. Exposes all
 * fields including owner-only data (price tiers, media URLs, internal status).
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_account_id' => $this->business_account_id,
            'fabric_type_id' => $this->fabric_type_id,
            'material_id' => $this->material_id,
            'governorate_id' => $this->governorate_id,
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
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'colors' => $this->whenLoaded('colors', fn () => $this->colors->pluck('id', 'name_en')),
            'price_tiers' => ProductPriceTierResource::collection($this->whenLoaded('priceTiers')),
            'media' => ProductMediaResource::collection($this->whenLoaded('media')),
            'fabric_type' => $this->whenLoaded('fabricType', fn () => $this->fabricType->localizedName()),
            'material' => $this->whenLoaded('material', fn () => $this->material->localizedName()),
            'governorate' => $this->whenLoaded('governorate', fn () => $this->governorate->localizedName()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
