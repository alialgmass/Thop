<?php

namespace Modules\Search\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\ProductMediaResource;
use Modules\Catalog\Http\Resources\ProductPriceTierResource;

/**
 * Buyer-facing product detail (US-SRC-05): full spec, all images, price tiers,
 * and the supplier identity + verified badge the client needs for the
 * "Contact" / "Request Quotation" CTAs (the inquiry itself is Phase 6).
 * Seller-internal fields (status, rejection_reason, created_by) are omitted.
 */
class ProductDetailResource extends JsonResource
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
            'price_tiers' => ProductPriceTierResource::collection($this->whenLoaded('priceTiers')),
            'media' => ProductMediaResource::collection($this->whenLoaded('media')),
            'supplier' => $this->whenLoaded('businessAccount', fn () => [
                'id' => $this->businessAccount->id,
                'company_name' => $this->businessAccount->company_name,
                'governorate' => $this->businessAccount->relationLoaded('governorate')
                    ? $this->businessAccount->governorate?->localizedName()
                    : null,
                'verified' => $this->businessAccount->isVerified(),
            ]),
            'actions' => [
                'contact' => ['supplier_id' => $this->business_account_id, 'product_id' => $this->id],
                'request_quotation' => ['supplier_id' => $this->business_account_id, 'product_id' => $this->id],
            ],
        ];
    }
}
