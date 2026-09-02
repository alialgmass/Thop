<?php

namespace Modules\Verification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
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
 * A {@see VerificationNotPendingException} propagates and is rendered as a
 * 409 envelope (custom code 4092).
 */
class AdminVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly VerificationPolicy $policy,
        private readonly DecideVerificationRequest $decide,
    ) {}

    public function queue(Request $request): JsonResponse
    {
        abort_unless($this->policy->viewAny($request->user()), 403);

        $requests = VerificationRequest::query()
            ->where('status', VerificationRequestStatus::Pending)
            ->whereNotNull('submitted_at')
            ->with('documents')
            ->latest('submitted_at')
            ->paginate();

        $payload = VerificationRequestResource::collection($requests)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['verification_requests' => $payload])
            ->apiResponse();
    }

    public function approve(Request $request, VerificationRequest $verificationRequest): JsonResponse
    {
        abort_unless($this->policy->review($request->user(), $verificationRequest), 403);

        $this->decide->approve($verificationRequest, $request->user());

        return $this
            ->apiBody(['verification_request' => new VerificationRequestResource($verificationRequest->load('documents'))])
            ->apiResponse();
    }

    public function reject(RejectVerificationRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        abort_unless($this->policy->review($request->user(), $verificationRequest), 403);

        $this->decide->reject($verificationRequest, $request->user(), (string) $request->string('reason'));

        return $this
            ->apiBody(['verification_request' => new VerificationRequestResource($verificationRequest->load('documents'))])
            ->apiResponse();
    }
}
