<?php

namespace Modules\Subscriptions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Database\Factories\SubscriptionFactory;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Events\SubscriptionExpired;
use Modules\Subscriptions\Support\SyncsEntitlementRows;

/**
 * @property int $id
 * @property int $business_account_id
 * @property int $plan_id
 * @property SubscriptionStatus $status
 * @property Carbon|null $current_period_end
 * @property Carbon|null $trial_ends_at
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'business_account_id',
        'plan_id',
        'status',
        'current_period_end',
        'trial_ends_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'current_period_end' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    protected static function booted(): void
    {
        // Every subscription gets its entitlement snapshot taken the moment
        // it's created — this is the only automatic snapshot point. A later
        // plan-id change (downgrade taking effect at period end) or an
        // admin's explicit "apply to existing" both call
        // syncEntitlementsFromPlan() themselves; nothing else does, which is
        // what keeps a plain plan edit non-retroactive (US-SUB-05).
        static::created(fn (self $subscription) => $subscription->syncEntitlementsFromPlan());
    }

    /**
     * @return BelongsTo<BusinessAccount, $this>
     */
    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(BusinessAccount::class);
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * @return HasMany<SubscriptionUsageCounter, $this>
     */
    public function usageCounters(): HasMany
    {
        return $this->hasMany(SubscriptionUsageCounter::class);
    }

    /**
     * @return HasMany<SubscriptionEntitlementSnapshot, $this>
     */
    public function entitlementSnapshots(): HasMany
    {
        return $this->hasMany(SubscriptionEntitlementSnapshot::class);
    }

    /**
     * Overwrite this subscription's entitlement snapshot with its plan's
     * current entitlements — upsert every key the plan still has, and drop
     * any snapshot row for a key the plan no longer defines.
     */
    public function syncEntitlementsFromPlan(): void
    {
        SyncsEntitlementRows::sync($this->entitlementSnapshots(), $this->plan->entitlements->pluck('value', 'key')->all());
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isExpired(): bool
    {
        if ($this->status === SubscriptionStatus::Expired) {
            return true;
        }

        // Active subscription past its paid period end → expired
        if ($this->current_period_end && $this->current_period_end->isPast()) {
            return true;
        }

        // Trial subscription past its trial end → expired
        if ($this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        return false;
    }

    public function isTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * Scope to the currently active, non-expired subscription for a business.
     *
     * A subscription is only "active" when its status is Active AND its paid
     * period (and trial, if any) has not yet elapsed.
     */
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActiveForBusiness(Builder $query, int $businessAccountId): Builder
    {
        return $query->where('business_account_id', $businessAccountId)->currentlyActive();
    }

    /**
     * Status Active AND the paid period (and trial, if any) has not elapsed.
     * The business-agnostic half of {@see scopeActiveForBusiness}, reused by the
     * product-visibility gate (BR-SUB-03).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->where(fn ($period) => $period->whereNull('current_period_end')
                ->orWhere('current_period_end', '>', now()))
            ->where(fn ($trial) => $trial->whereNull('trial_ends_at')
                ->orWhere('trial_ends_at', '>', now()));
    }

    /**
     * Mark subscription as expired (BR-SUB-03: restricted state).
     */
    public function markExpired(): void
    {
        if ($this->status === SubscriptionStatus::Restricted) {
            return;
        }

        $this->update([
            'status' => SubscriptionStatus::Restricted,
        ]);

        SubscriptionExpired::dispatch($this);
    }
}
