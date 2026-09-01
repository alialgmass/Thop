<?php

namespace Modules\Verification\Http\Controllers;

use App\Http\Concerns\RendersApiErrors;
use App\Http\Concerns\ResolvesRequestUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Verification\Actions\DecideVerificationRequest;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Exceptions\VerificationNotPendingException;
use Modules\Verification\Http\Requests\RejectVerificationRequest;
use Modules\Verification\Http\Resources\VerificationRequestResource;
use Modules\Verification\Models\VerificationRequest;
use Modules\Verification\Policies\VerificationPolicy;

/**
 * Admin review of verification requests (US-ADM-01). The decision itself is
 * applied by {@see DecideVerificationRequest} — shared with the Filament panel —
 * so the audit-log write (BR-ADM-01) and domain events live in one place.
 */
class AdminVerificationController extends Controller
{
    use RendersApiErrors;
    use ResolvesRequestUser;

    public function __construct(
        private readonly VerificationPolicy $policy,
        private readonly DecideVerificationRequest $decide,
    ) {}

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
        $admin = $this->currentUser($request);
        abort_unless($this->policy->review($admin, $verificationRequest), 403);

        try {
            $this->decide->approve($verificationRequest, $admin);
        } catch (VerificationNotPendingException) {
            return $this->apiError(__('verification::messages.not_pending'), 'status', 409);
        }

        return (new VerificationRequestResource($verificationRequest->load('documents')))->response();
    }

    public function reject(RejectVerificationRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $admin = $this->currentUser($request);
        abort_unless($this->policy->review($admin, $verificationRequest), 403);

        try {
            $this->decide->reject($verificationRequest, $admin, (string) $request->string('reason'));
        } catch (VerificationNotPendingException) {
            return $this->apiError(__('verification::messages.not_pending'), 'status', 409);
        }

        return (new VerificationRequestResource($verificationRequest->load('documents')))->response();
    }
}
