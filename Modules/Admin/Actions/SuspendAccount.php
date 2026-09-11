<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Events\AccountSuspended;
use Modules\Admin\Exceptions\AccountAlreadySuspendedException;
use Modules\Admin\Exceptions\CannotModerateAdminAccountException;
use Modules\Admin\Models\AuditLog;
use Modules\Auth\Enums\UserStatus;

/**
 * The single place an admin suspends a user account is applied (US-ADM-07,
 * Phase 9 · T8). Shared by the REST endpoint and the Filament panel.
 *
 * `confirm=true` itself is enforced at the request/form layer (matching
 * `Modules\Subscriptions\Http\Requests\ApplyPlanToExistingRequest`'s
 * convention), not here — this class only guards the state transition.
 */
class SuspendAccount
{
    public function handle(User $target, User $admin): User
    {
        if ($target->status === UserStatus::Suspended) {
            throw new AccountAlreadySuspendedException;
        }

        if ($target->hasRole('admin')) {
            throw new CannotModerateAdminAccountException;
        }

        DB::transaction(function () use ($target, $admin): void {
            $target->forceFill(['status' => UserStatus::Suspended])->save();

            // No token restore on reactivation (ReactivateAccount) — a
            // suspended session is gone for good; a reactivated user signs
            // in again like anyone else.
            $target->tokens()->delete();

            AuditLog::record($admin, AuditAction::AccountSuspended, $target, [
                'phone' => $target->phone,
            ]);
        });

        AccountSuspended::dispatch($target);

        return $target;
    }
}
