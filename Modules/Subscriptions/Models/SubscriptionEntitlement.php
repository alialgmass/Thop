<?php

namespace Modules\Subscriptions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property string $key
 * @property string $value
 */
class SubscriptionEntitlement extends Model
{
    /** @use HasFactory<SubscriptionEntitlement> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
