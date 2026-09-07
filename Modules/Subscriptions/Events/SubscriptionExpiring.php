<?php

namespace Modules\Subscriptions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Subscriptions\Models\Subscription;

/**
 * Fired by the `subscriptions:notify-expiring` scheduled command when a
 * subscription's paid period ends within the reminder window (US-SUB-08).
 * The Notifications module attaches `subscription_expiring` (§14).
 */
class SubscriptionExpiring
{
    use Dispatchable;

    public function __construct(public readonly Subscription $subscription) {}
}
