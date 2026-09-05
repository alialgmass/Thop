<?php

namespace Modules\Inquiries\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A seller's reply to an RFQ (US-INQ-03). `expired` is computed from
 * `valid_until` on the fly — an expired offer renders as expired, never as
 * current pricing.
 */
class QuotationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rfq_id' => $this->rfq_id,
            'price' => $this->price,
            'availability_note' => $this->availability_note,
            'valid_until' => $this->valid_until,
            'expired' => $this->isExpired(),
            'created_at' => $this->created_at,
        ];
    }
}
