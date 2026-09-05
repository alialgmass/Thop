<?php

namespace Modules\Inquiries\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Confirmation that a report was recorded (US-INQ-09) — no moderation state
 * to expose yet, that's Phase 9's ticket queue.
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
