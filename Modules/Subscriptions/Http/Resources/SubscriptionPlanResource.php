<?php

namespace Modules\Subscriptions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $account_type
 * @property-read float|null $price
 * @property-read string|null $billing_cycle
 * @property-read mixed $entitlements
 */
class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'account_type' => $this->account_type,
            'price' => $this->price !== null ? (float) $this->price : null,
            'billing_cycle' => $this->billing_cycle?->value,
            'entitlements' => $this->whenLoaded('entitlements', $this->entitlements->map(
                fn ($e) => ['key' => $e->key, 'value' => $e->value]
            )->values()),
        ];
    }
}
