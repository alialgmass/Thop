<?php

namespace Modules\Verification\Http\Controllers;

use App\Http\Concerns\RendersApiErrors;
use App\Http\Concerns\ResolvesRequestUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationRejected;
use Modules\Verification\Http\Requests\RejectVerificationRequest;
use Modules\Verification\Http\Resources\VerificationRequestResource;
use Modules\Verification\Models\VerificationRequest;
use Modules\Verification\Policies\VerificationPolicy;

/**
 * Admin review of verification requests (US-ADM-01). Every decision is written
 * to the immutable audit log (BR-ADM-01) and fires a domain event for Phase 8.
 */
class AdminVerificationController extends Controller
{
    use RendersApiErrors;
    use ResolvesRequestUser;

    public function __construct(private readonly VerificationPolicy $policy) {}

    public function queue(Request $request): AnonymousResourceCollection
    {
        abort_unless($this->policy->viewAny($this->currentUser($request)), 403);

        $requests = VerificationRequest::query()
            ->where('status', VerificationRequestStatus::Pending)
            ->whereNotNull('submitted_at')
            ->with('documents')
            ->latest('submitted_at')
            ->paginate();

        return VerificationRequestResource::collection($requests);
    }

    public function approve(Request $request, VerificationRequest $verificationRequest): JsonResponse
    {
        return $this->decide(
            $request,
            $verificationRequest,
            VerificationRequestStatus::Approved,
            VerificationStatus::Verified,
            AuditAction::VerificationApproved,
            null,
        );
    }

    public function reject(RejectVerificationRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        return $this->decide(
            $request,
            $verificationRequest,
            VerificationRequestStatus::Rejected,
            VerificationStatus::Rejected,
            AuditAction::VerificationRejected,
            (string) $request->string('reason'),
        );
    }

    private function decide(
        Request $request,
        VerificationRequest $verificationRequest,
        VerificationRequestStatus $outcome,
        VerificationStatus $businessStatus,
        AuditAction $action,
        ?string $reason,
    ): JsonResponse {
        $admin = $this->currentUser($request);
        abort_unless($this->policy->review($admin, $verificationRequest), 403);

        // US-ADM-01: only a *pending*, actually-submitted request can be decided.
        if (! $verificationRequest->isAwaitingReview() || $verificationRequest->submitted_at === null) {
            return $this->apiError(__('verification::messages.not_pending'), 'status', 409);
        }

        DB::transaction(function () use ($admin, $verificationRequest, $outcome, $businessStatus, $action, $reason): void {
            $verificationRequest->forceFill([
                'status' => $outcome,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $verificationRequest->businessAccount->forceFill([
                'verification_status' => $businessStatus,
            ])->save();

            AuditLog::record($admin, $action, $verificationRequest, array_filter([
                'business_account_id' => $verificationRequest->business_account_id,
                'reason' => $reason,
            ], fn ($value): bool => $value !== null));
        });

        $reason === null
            ? VerificationApproved::dispatch($verificationRequest)
            : VerificationRejected::dispatch($verificationRequest, $reason);

        return (new VerificationRequestResource($verificationRequest->load('documents')))->response();
    }
}
