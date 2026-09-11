<?php

namespace Modules\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * A subscription's own copy of its entitlements, taken from its plan when
 * the subscription starts or switches plan (see {@see Subscription::syncEntitlementsFromPlan()}).
 * This — not `SubscriptionPlan::entitlements()` — is what {@see EntitlementService}
 * reads, so a plan edit is non-retroactive by default (US-SUB-05).
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $key
 * @property string $value
 */
class SubscriptionEntitlementSnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
