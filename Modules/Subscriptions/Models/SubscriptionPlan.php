<?php

namespace Modules\Subscriptions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscriptions\Database\Factories\SubscriptionPlanFactory;
use Modules\Subscriptions\Enums\BillingCycle;

/**
 * @property int $id
 * @property string $account_type
 * @property string $name
 * @property float|null $price
 * @property BillingCycle|null $billing_cycle
 * @property bool $is_active
 */
class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_cycle' => BillingCycle::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): SubscriptionPlanFactory
    {
        return SubscriptionPlanFactory::new();
    }

    /**
     * @return HasMany<SubscriptionEntitlement, $this>
     */
    public function entitlements(): HasMany
    {
        return $this->hasMany(SubscriptionEntitlement::class, 'plan_id');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Get an entitlement value by key.
     */
    public function getEntitlement(string $key): ?string
    {
        return $this->entitlements->firstWhere('key', $key)?->value;
    }

    /**
     * Check if a boolean entitlement is enabled.
     */
    public function hasEntitlement(string $key): bool
    {
        $value = $this->getEntitlement($key);

        return $value !== null && $value !== 'false' && $value !== '0';
    }
}
