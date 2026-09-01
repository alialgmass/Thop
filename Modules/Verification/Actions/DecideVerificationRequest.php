<?php

namespace Modules\Verification\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationRejected;
use Modules\Verification\Exceptions\VerificationNotPendingException;
use Modules\Verification\Models\VerificationRequest;

/**
 * The single place an admin decision on a verification request is applied
 * (US-ADM-01). Shared by the REST endpoint and the Filament panel so the state
 * guard, audit-log write (BR-ADM-01) and domain event stay in one place.
 */
class DecideVerificationRequest
{
    public function approve(VerificationRequest $request, User $admin): VerificationRequest
    {
        return $this->apply(
            $request,
            $admin,
            VerificationRequestStatus::Approved,
            VerificationStatus::Verified,
            AuditAction::VerificationApproved,
            null,
        );
    }

    public function reject(VerificationRequest $request, User $admin, string $reason): VerificationRequest
    {
        return $this->apply(
            $request,
            $admin,
            VerificationRequestStatus::Rejected,
            VerificationStatus::Rejected,
            AuditAction::VerificationRejected,
            $reason,
        );
    }

    private function apply(
        VerificationRequest $request,
        User $admin,
        VerificationRequestStatus $outcome,
        VerificationStatus $businessStatus,
        AuditAction $action,
        ?string $reason,
    ): VerificationRequest {
        if (! $request->isAwaitingReview() || $request->submitted_at === null) {
            throw new VerificationNotPendingException;
        }

        DB::transaction(function () use ($request, $admin, $outcome, $businessStatus, $action, $reason): void {
            $request->forceFill([
                'status' => $outcome,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $request->businessAccount->forceFill(['verification_status' => $businessStatus])->save();

            AuditLog::record($admin, $action, $request, array_filter([
                'business_account_id' => $request->business_account_id,
                'reason' => $reason,
            ], fn ($value): bool => $value !== null));
        });

        $reason === null
            ? VerificationApproved::dispatch($request)
            : VerificationRejected::dispatch($request, $reason);

        return $request;
    }
}
