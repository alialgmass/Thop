<?php

namespace Modules\Subscriptions\Support;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscriptions\Actions\ManageSubscriptionPlan;
use Modules\Subscriptions\Models\Subscription;

/**
 * The "replace-all key/value rows" shape shared by {@see ManageSubscriptionPlan::syncEntitlements()}
 * (plan → `subscription_entitlements`) and {@see Subscription::syncEntitlementsFromPlan()}
 * (subscription → `subscription_entitlement_snapshots`) — same operation
 * against two parallel tables, kept in one place.
 */
class SyncsEntitlementRows
{
    /**
     * @param  HasMany<*, *>  $relation
     * @param  array<string, string>  $pairs  key => value
     */
    public static function sync(HasMany $relation, array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $relation->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $relation->whereNotIn('key', array_keys($pairs))->delete();
    }
}
