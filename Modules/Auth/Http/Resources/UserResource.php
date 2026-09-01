<?php

namespace Modules\Auth\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Enums\AccountType;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'email' => $this->email,
            'account_type' => $this->account_type?->value,
            'status' => $this->status->value,
            'language' => $this->language,
            'next_onboarding_step' => $this->account_type instanceof AccountType
                ? $this->account_type->nextOnboardingStep()
                : 'account_type_selection',
        ];
    }
}
