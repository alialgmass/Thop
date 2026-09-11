<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Exceptions\PhoneAlreadyRegisteredException;
use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Businesses\Models\BusinessAccount;

/**
 * The single place an admin onboards a supplier on their behalf is applied
 * (US-ADM-10, Phase 9 · T10, issue #39). Deliberately reuses only what's
 * actually shareable between this and {@see RegisterController}: the exact
 * same duplicate-phone rule ({@see PhoneAlreadyRegisteredException}). The
 * `User::create()` call itself is NOT the same call — this doesn't invoke
 * `RegisterController`, since the two flows set genuinely different fields
 * (status Active + account_type set immediately here, vs. PendingTypeSelection
 * + null there; no OTP handoff here at all, since the admin is vouching for
 * the phone number directly). What matters for the acceptance criteria is
 * that the *result* is indistinguishable from a self-registered account
 * afterward, except `onboarded_by_admin` — not that the code path is shared
 * beyond the duplicate-phone check. Verification still goes through the
 * normal flow — this doesn't touch it.
 */
class OnboardSupplier
{
    /**
     * @param  array{phone: string, account_type: string, password: string, email: ?string, language: ?string, company_name: string, activity: string, governorate_id: int, address: string, contact_person: string, contact_channels?: array}  $data
     */
    public function handle(array $data, User $admin): BusinessAccount
    {
        if (User::query()->where('phone', $data['phone'])->exists()) {
            throw new PhoneAlreadyRegisteredException;
        }

        $business = DB::transaction(function () use ($data, $admin): BusinessAccount {
            $user = User::query()->create([
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'language' => $data['language'] ?: 'ar',
                'account_type' => $data['account_type'],
                'status' => UserStatus::Active,
            ]);

            $business = $user->businessAccount()->create([
                'company_name' => $data['company_name'],
                'activity' => $data['activity'],
                'governorate_id' => $data['governorate_id'],
                'address' => $data['address'],
                'contact_person' => $data['contact_person'],
                'contact_channels' => $data['contact_channels'] ?? null,
                'onboarded_by_admin' => true,
            ]);

            AuditLog::record($admin, AuditAction::SupplierOnboarded, $business, [
                'phone' => $user->phone,
                'account_type' => $data['account_type'],
            ]);

            return $business;
        });

        return $business;
    }
}
