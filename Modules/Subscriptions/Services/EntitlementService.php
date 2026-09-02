<?php

namespace Modules\Subscriptions\Services;

use Illuminate\Support\Facades\DB;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionUsageCounter;

/**
 * Server-side entitlement enforcement (BR-SUB-01, SEC-NFR-04).
 *
 * All capability/limit checks MUST go through this service.
 * Never trust client-supplied plan or entitlement claims.
 *
 * Usage:
 *   app(EntitlementService::class)->can($business, 'product_limit')
 *   app(EntitlementService::class)->can($business, 'search_priority')
 */
class EntitlementService
{
    /**
     * Check if a business has a specific capability enabled.
     *
     * For boolean capabilities (search_priority, featured_placement, etc.):
     *   returns true if entitlement exists and is truthy.
     *
     * For numeric limits (product_limit, inquiry_limit, etc.):
     *   returns true if current usage is below the limit.
     */
    public function can(BusinessAccount $business, string $key): bool
    {
        $subscription = $this->getActiveSubscription($business);

        if (! $subscription) {
            return false;
        }

        $entitlement = $subscription->plan->entitlements->firstWhere('key', $key);

        if (! $entitlement) {
            return false;
        }

        return $this->evaluateEntitlement($entitlement->value, $key, $subscription);
    }

    /**
     * Get the raw entitlement value for a key.
     */
    public function get(BusinessAccount $business, string $key): ?string
    {
        $subscription = $this->getActiveSubscription($business);

        if (! $subscription) {
            return null;
        }

        return $subscription->plan->entitlements->firstWhere('key', $key)?->value;
    }

    /**
     * Increment a usage counter (e.g., after creating a product).
     */
    public function incrementUsage(BusinessAccount $business, string $key, int $amount = 1): void
    {
        $subscription = $this->getActiveSubscription($business);

        if (! $subscription) {
            return;
        }

        // Atomically increment or seed the counter (works on all SQL drivers).
        $counter = SubscriptionUsageCounter::firstOrCreate(
            [
                'subscription_id' => $subscription->getKey(),
                'key' => $key,
            ],
            ['current_value' => $amount],
        );

        if ($counter->wasRecentlyCreated) {
            return;
        }

        $counter->increment('current_value', $amount);
    }

    /**
     * Decrement a usage counter (e.g., after deleting a product).
     */
    public function decrementUsage(BusinessAccount $business, string $key, int $amount = 1): void
    {
        $subscription = $this->getActiveSubscription($business);

        if (! $subscription) {
            return;
        }

        DB::table('subscription_usage_counters')
            ->where('subscription_id', $subscription->getKey())
            ->where('key', $key)
            ->where('current_value', '>=', $amount)
            ->decrement('current_value', $amount);
    }

    /**
     * Get the current usage for a key.
     */
    public function currentUsage(BusinessAccount $business, string $key): int
    {
        $subscription = $this->getActiveSubscription($business);

        if (! $subscription) {
            return 0;
        }

        $counter = SubscriptionUsageCounter::where('subscription_id', $subscription->getKey())
            ->where('key', $key)
            ->first();

        return $counter?->current_value ?? 0;
    }

    /**
     * Get the active subscription for a business, or null.
     */
    public function getActiveSubscription(BusinessAccount $business): ?Subscription
    {
        return Subscription::activeForBusiness($business->getKey())->first();
    }

    /**
     * Evaluate whether a single entitlement is satisfied.
     */
    private function evaluateEntitlement(string $value, string $key, Subscription $subscription): bool
    {
        // Boolean-style entitlements: "true"/"false"/"0"/"1"
        if (in_array($value, ['true', 'false', '0', '1'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // Numeric limit entitlements: compare usage vs limit
        // Check if this key corresponds to a usage counter
        $counterKey = match ($key) {
            'product_limit' => 'product_count',
            'inquiry_limit' => 'inquiry_count',
            default => null,
        };

        if ($counterKey) {
            $counter = SubscriptionUsageCounter::where('subscription_id', $subscription->getKey())
                ->where('key', $counterKey)
                ->first();

            $currentUsage = $counter?->current_value ?? 0;
            $limit = (int) $value;

            // Non-numeric string limits like "Limited", "Large" — treat as unlimited
            if ($limit === 0 && is_numeric($value) === false) {
                return true;
            }

            return $currentUsage < $limit;
        }

        // Non-numeric string values (e.g., "basic", "advanced", "dedicated") — capability exists
        return true;
    }
}
