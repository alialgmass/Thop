<?php

namespace Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Admin\Models\AuditLog;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationRejected;
use Modules\Verification\Http\Requests\RejectVerificationRequest;
use Modules\Verification\Http\Resources\VerificationRequestResource;
use Modules\Verification\Models\VerificationRequest;

/**
 * Admin review of verification requests (US-ADM-01). Every decision is written
 * to the immutable audit log (BR-ADM-01) and fires a domain event for Phase 8.
 */
class AdminVerificationController extends Controller
{
    use AuthorizesRequests;

    public function queue(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

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
        $this->authorize('review', $verificationRequest);

        $verificationRequest->forceFill([
            'status' => VerificationRequestStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $verificationRequest->businessAccount->forceFill([
            'verification_status' => VerificationStatus::Verified,
        ])->save();

        $this->log($request->user(), 'verification.approved', $verificationRequest, []);

        VerificationApproved::dispatch($verificationRequest);

        return (new VerificationRequestResource($verificationRequest->load('documents')))->response();
    }

    public function reject(RejectVerificationRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $this->authorize('review', $verificationRequest);

        $reason = (string) $request->string('reason');

        $verificationRequest->forceFill([
            'status' => VerificationRequestStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $verificationRequest->businessAccount->forceFill([
            'verification_status' => VerificationStatus::Rejected,
        ])->save();

        $this->log($request->user(), 'verification.rejected', $verificationRequest, ['reason' => $reason]);

        VerificationRejected::dispatch($verificationRequest, $reason);

        return (new VerificationRequestResource($verificationRequest->load('documents')))->response();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function log(User $actor, string $action, VerificationRequest $verificationRequest, array $metadata): void
    {
        AuditLog::record($actor, $action, $verificationRequest, array_merge($metadata, [
            'business_account_id' => $verificationRequest->business_account_id,
        ]));
    }
}
