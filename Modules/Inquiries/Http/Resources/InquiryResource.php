<?php

namespace Modules\Inquiries\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An inquiry as seen by either party — the seller's side of this is the
 * Lead Management screen (US-ANL-03).
 */
class InquiryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'buyer_id' => $this->buyer_id,
            'seller_business_id' => $this->seller_business_id,
            'product_id' => $this->product_id,
            'message' => $this->message,
            'lead_status' => $this->lead_status,
            // `updated_at` IS the last activity: an RFQ or quotation touches the
            // inquiry (US-ANL-03), as does a lead-status change.
            'last_activity_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
