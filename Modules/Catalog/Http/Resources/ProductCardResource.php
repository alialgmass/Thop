<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Public-facing product card resource. Exposes CDN URLs for media and the
 * essential buyer-facing fields. Does NOT expose internal price tiers or
 * business-account metadata.
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
            'fabric_type' => $this->whenLoaded('fabricType', fn () => $this->fabricType->localizedName()),
            'material' => $this->whenLoaded('material', fn () => $this->material->localizedName()),
            'governorate' => $this->whenLoaded('governorate', fn () => $this->governorate->localizedName()),
            'colors' => $this->whenLoaded('colors', fn () => $this->colors->pluck('id', 'name_en')),
            'price_tiers' => ProductPriceTierResource::collection($this->whenLoaded('priceTiers')),
            'media' => ProductMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
