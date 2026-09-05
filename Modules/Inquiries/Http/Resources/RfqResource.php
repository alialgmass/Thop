<?php

namespace Modules\Inquiries\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A structured request-for-quotation (US-INQ-02), with its quotation
 * replies (US-INQ-03) inlined so the buyer/seller thread view doesn't need a
 * second round-trip.
 */
class RfqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inquiry_id' => $this->inquiry_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'color_id' => $this->color_id,
            'needed_by_date' => $this->needed_by_date,
            'below_moq' => $this->isBelowMoq(),
            'created_at' => $this->created_at,
            'quotations' => QuotationResource::collection($this->whenLoaded('quotations')),
        ];
    }
}
