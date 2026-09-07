<?php

namespace Modules\Subscriptions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Subscriptions\Models\Subscription;

/**
 * Fired when a subscription lapses into the restricted state (BR-SUB-03) —
 * from {@see Subscription::markExpired()}. The
 * Notifications module attaches `subscription_expired` (§14); the seller's
 * products are already hidden by the catalog visibility scope.
 */
class SubscriptionExpired
{
    use Dispatchable;

    public function __construct(public readonly Subscription $subscription) {}
}
