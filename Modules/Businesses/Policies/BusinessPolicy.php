<?php

namespace Modules\Businesses\Policies;

use App\Models\User;
use Modules\Businesses\Models\BusinessAccount;

/**
 * Authorization for the company profile, per the Spec Section 8 matrix:
 * a business-account owner has CRU on their own profile; an admin has RU;
 * nobody else has anything. There is no delete in R1.
 */
class BusinessPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return in_array($ability, ['view', 'update'], true) ? true : null;
        }

        return null;
    }

    /**
     * Create a profile: business-account type, and none exists yet.
     */
    public function create(User $user): bool
    {
        return $user->isBusinessAccount() && $user->businessAccount()->doesntExist();
    }

    public function view(User $user, BusinessAccount $business): bool
    {
        return $business->user_id === $user->id;
    }

    public function update(User $user, BusinessAccount $business): bool
    {
        return $business->user_id === $user->id;
    }
}
