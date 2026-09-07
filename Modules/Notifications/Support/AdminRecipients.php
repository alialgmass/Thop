<?php

namespace Modules\Notifications\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The admin fan-out target for queue notifications (verification submitted,
 * product submitted). Every user holding the `admin` role — sub-user scoping
 * (ACC-FR-08) is R2.
 *
 * Uses a relation query rather than the spatie `role()` scope on purpose: the
 * scope throws when the role has never been created, and a domain event must
 * not fail just because no admin exists in that context.
 */
class AdminRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function all(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();
    }
}
