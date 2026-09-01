<?php

namespace Modules\Businesses\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Businesses\Models\BusinessAccount;

/**
 * @mixin BusinessAccount
 */
class BusinessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $public = [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'activity' => $this->activity,
            'governorate_id' => $this->governorate_id,
            'verified' => $this->isVerified(),
        ];

        if (! $request->user()?->can('view', $this->resource)) {
            return $public;
        }

        return array_merge($public, [
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'contact_channels' => $this->contact_channels ?? [],
            'verification_status' => $this->verification_status->value,
            'onboarded_by_admin' => $this->onboarded_by_admin,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
