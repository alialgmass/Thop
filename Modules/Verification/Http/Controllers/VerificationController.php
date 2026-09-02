<?php

namespace Modules\Verification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationSubmitted;
use Modules\Verification\Http\Requests\UploadVerificationDocumentRequest;
use Modules\Verification\Http\Resources\VerificationDocumentResource;
use Modules\Verification\Http\Resources\VerificationStatusResource;
use Modules\Verification\Models\VerificationDocument;
use Modules\Verification\Models\VerificationRequest;
use Modules\Verification\Policies\VerificationPolicy;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner-facing verification flow: upload documents, submit for review, read the
 * status, download own documents. Admin review lives in
 * {@see AdminVerificationController}. Authorization is the injected
 * {@see VerificationPolicy} — its abilities span BusinessAccount /
 * VerificationRequest / VerificationDocument, so it is called directly rather
 * than registered against one model.
 */
class VerificationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VerificationPolicy $policy) {}

    public function uploadDocument(UploadVerificationDocumentRequest $request, BusinessAccount $business): JsonResponse
    {
        abort_unless($this->policy->upload($request->user(), $business), 403);

        $file = $request->file('file');
        $disk = (string) config('verification.disk');
        $key = "business/{$business->id}/".Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->putFileAs('', $file, $key);

        $document = $this->openRequestFor($business)->documents()->create([
            'document_type_id' => $request->integer('document_type_id'),
            'disk' => $disk,
            'path' => $key,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return $this
            ->apiCode(201)
            ->apiBody(['document' => new VerificationDocumentResource($document->load('verificationRequest'))])
            ->apiResponse();
    }

    public function submit(Request $request, BusinessAccount $business): JsonResponse
    {
        abort_unless($this->policy->submit($request->user(), $business), 403);

        $verificationRequest = $business->verificationRequests()
            ->where('status', VerificationRequestStatus::Pending)
            ->withCount('documents')
            ->latest('id')
            ->first();

        if ($verificationRequest === null || $verificationRequest->documents_count === 0) {
            $message = __('verification::messages.no_documents');

            throw ExceptionResponse::instance($message, 422)
                ->setCustomBody(['documents' => [$message]]);
        }

        $verificationRequest->forceFill(['submitted_at' => now()])->save();
        $business->forceFill(['verification_status' => VerificationStatus::Pending])->save();

        VerificationSubmitted::dispatch($verificationRequest);

        return $this
            ->apiBody(['verification' => new VerificationStatusResource($business->refresh()->load('latestVerificationRequest.documents'))])
            ->apiResponse();
    }

    public function status(Request $request, BusinessAccount $business): JsonResponse
    {
        abort_unless($this->policy->viewStatus($request->user(), $business), 403);

        return $this
            ->apiBody(['verification' => new VerificationStatusResource($business->load('latestVerificationRequest.documents'))])
            ->apiResponse();
    }

    public function download(Request $request, BusinessAccount $business, VerificationDocument $document): StreamedResponse
    {
        abort_unless($document->verificationRequest->business_account_id === $business->id, 404);
        abort_unless($this->policy->download($request->user(), $document), 403);

        return $document->downloadResponse();
    }

    private function openRequestFor(BusinessAccount $business): VerificationRequest
    {
        return $business->verificationRequests()
            ->where('status', VerificationRequestStatus::Pending)
            ->latest('id')
            ->first()
            ?? $business->verificationRequests()->create();
    }
}
