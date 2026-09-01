<?php

namespace Modules\Verification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Businesses\Models\BusinessAccount;

/**
 * @mixin BusinessAccount
 */
class VerificationStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latest = $this->latestVerificationRequest;

        return [
            'business_id' => $this->id,
            'verification_status' => $this->verification_status->value,
            'verified' => $this->isVerified(),
            'rejection_reason' => $latest?->rejection_reason,
            'reviewed_at' => $latest?->reviewed_at,
            'documents' => VerificationDocumentResource::collection(
                $latest?->documents ?? collect(),
            ),
        ];
    }
}
