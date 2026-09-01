<?php

namespace Modules\Verification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
            // Download is a separate policy-gated endpoint — never a raw path/URL.
            'download_url' => route('api.verification.documents.download', [
                'business' => $this->verificationRequest->business_account_id,
                'document' => $this->id,
            ]),
        ];
    }
}
