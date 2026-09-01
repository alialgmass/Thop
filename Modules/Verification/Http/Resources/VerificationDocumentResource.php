<?php

namespace Modules\Verification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Modules\Verification\Models\VerificationDocument;

/**
 * @mixin VerificationDocument
 */
class VerificationDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type_id' => $this->document_type_id,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'original_name' => $this->original_name,
            'uploaded_at' => $this->created_at,
            // A signed, time-limited link — never a raw path or a plain URL
            // (spec Section 12 / Section 15). The route also re-checks the
            // policy, so the signature is defence in depth, not the only gate.
            'download_url' => URL::temporarySignedRoute(
                'api.verification.documents.download',
                now()->addSeconds((int) config('verification.download_link_ttl_seconds')),
                [
                    'business' => $this->verificationRequest->business_account_id,
                    'document' => $this->id,
                ],
            ),
        ];
    }
}
