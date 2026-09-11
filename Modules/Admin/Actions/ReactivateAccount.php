<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Exceptions\AccountNotSuspendedException;
use Modules\Admin\Models\AuditLog;
use Modules\Auth\Enums\UserStatus;

/**
 * The single place an admin reactivates a suspended account is applied
 * (US-ADM-07, Phase 9 · T8). No token restore — a reactivated user signs in
 * again like anyone else.
 */
class ReactivateAccount
{
    public function handle(User $target, User $admin): User
    {
        if ($target->status !== UserStatus::Suspended) {
            throw new AccountNotSuspendedException;
        }

        DB::transaction(function () use ($target, $admin): void {
            $target->forceFill(['status' => UserStatus::Active])->save();

            AuditLog::record($admin, AuditAction::AccountReactivated, $target, [
                'phone' => $target->phone,
            ]);
        });

        return $target;
    }
}
