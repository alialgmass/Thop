<?php

namespace Modules\Subscriptions\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Subscriptions\Models\Subscription;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any subscriptions.
     * Only admins list across all subscriptions; owners use the API per-record.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the subscription.
     * Only the business owner (or an admin) can view a subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        return $user->id === $subscription->businessAccount->user_id
            || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create (subscribe).
     */
    public function create(User $user): bool
    {
        return $user->businessAccount()->exists();
    }

    /**
     * Determine whether the user can update (upgrade/downgrade/cancel).
     * Only the business owner (or an admin) can modify a subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        return $user->id === $subscription->businessAccount->user_id
            || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete (cancel).
     * Only the business owner (or an admin) can cancel a subscription.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->id === $subscription->businessAccount->user_id
            || $user->hasRole('admin');
    }
}
