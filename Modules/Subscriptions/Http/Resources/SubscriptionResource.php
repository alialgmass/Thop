<?php

namespace Modules\Subscriptions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\Subscriptions\Models\SubscriptionPlan;

/**
 * @property-read int $id
 * @property-read int $business_account_id
 * @property-read int $plan_id
 * @property-read string $status
 * @property-read Carbon|null $current_period_end
 * @property-read Carbon|null $trial_ends_at
 * @property-read SubscriptionPlan $plan
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_account_id' => $this->business_account_id,
            'plan' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'account_type' => $this->plan->account_type,
                'price' => $this->plan->price,
                'billing_cycle' => $this->plan->billing_cycle?->value,
                'entitlements' => $this->whenLoaded('plan', fn () => $this->plan->entitlements->mapWithKeys(
                    fn ($e) => [$e->key => $e->value]
                )),
            ],
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'current_period_end' => $this->current_period_end?->toISOString(),
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'is_trial' => $this->isTrial(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
