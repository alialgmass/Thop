<?php

namespace Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationSubmitted;
use Modules\Verification\Http\Requests\UploadVerificationDocumentRequest;
use Modules\Verification\Http\Resources\VerificationDocumentResource;
use Modules\Verification\Http\Resources\VerificationStatusResource;
use Modules\Verification\Models\VerificationDocument;
use Modules\Verification\Models\VerificationRequest;
use Modules\Verification\Policies\VerificationPolicy;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly VerificationPolicy $policy) {}

    public function uploadDocument(UploadVerificationDocumentRequest $request, BusinessAccount $business): JsonResponse
    {
        $this->allow($this->policy->upload($this->user($request), $business));

        $verificationRequest = $this->openRequestFor($business);

        $file = $request->file('file');
        $disk = (string) config('verification.disk', 'verification');
        $path = "business/{$business->id}/".Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->putFileAs('', $file, $path);

        $document = $verificationRequest->documents()->create([
            'document_type_id' => $request->integer('document_type_id'),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return (new VerificationDocumentResource($document->load('verificationRequest')))
            ->response()
            ->setStatusCode(201);
    }

    public function submit(Request $request, BusinessAccount $business): JsonResponse
    {
        $this->allow($this->policy->submit($this->user($request), $business));

        $verificationRequest = $business->verificationRequests()
            ->where('status', VerificationRequestStatus::Pending)
            ->withCount('documents')
            ->latest('id')
            ->first();

        if ($verificationRequest === null || $verificationRequest->documents_count === 0) {
            return response()->json([
                'message' => __('verification::messages.no_documents'),
                'errors' => ['documents' => [__('verification::messages.no_documents')]],
            ], 422);
        }

        $verificationRequest->forceFill(['submitted_at' => now()])->save();
        $business->forceFill(['verification_status' => VerificationStatus::Pending])->save();

        VerificationSubmitted::dispatch($verificationRequest);

        return (new VerificationStatusResource($business->refresh()->load('latestVerificationRequest.documents')))
            ->response();
    }

    public function status(Request $request, BusinessAccount $business): JsonResponse
    {
        $this->allow($this->policy->viewStatus($this->user($request), $business));

        return (new VerificationStatusResource($business->load('latestVerificationRequest.documents')))->response();
    }

    public function download(Request $request, BusinessAccount $business, VerificationDocument $document): StreamedResponse
    {
        abort_unless($document->verificationRequest->business_account_id === $business->id, 404);

        $this->allow($this->policy->download($this->user($request), $document));

        return $document->downloadResponse();
    }

    private function openRequestFor(BusinessAccount $business): VerificationRequest
    {
        $open = $business->verificationRequests()
            ->where('status', VerificationRequestStatus::Pending)
            ->latest('id')
            ->first();

        return $open ?? $business->verificationRequests()->create();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function allow(bool $allowed): void
    {
        abort_unless($allowed, 403);
    }
}
