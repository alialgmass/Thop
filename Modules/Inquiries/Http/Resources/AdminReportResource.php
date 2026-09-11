<?php

namespace Modules\Inquiries\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inquiries\Models\Report;

/**
 * @mixin Report
 */
class AdminReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parties = $this->parties();

        return [
            'id' => $this->id,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'reporter' => ['id' => $this->reporter->id, 'phone' => $this->reporter->phone],
            'buyer' => ['id' => $parties['buyer']->id, 'phone' => $parties['buyer']->phone],
            'seller_business' => ['id' => $parties['sellerBusiness']->id, 'company_name' => $parties['sellerBusiness']->company_name],
            'resolved_by' => $this->resolved_by,
            'resolution_note' => $this->resolution_note,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
        ];
    }
}
