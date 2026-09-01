<?php

namespace Modules\Verification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property int $id
 * @property int $verification_request_id
 * @property int $document_type_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property string $original_name
 */
class VerificationDocument extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    /**
     * A time-limited response for an owner or admin to inspect this file. On a
     * driver that supports it (S3) this redirects to a signed URL; otherwise the
     * file is streamed directly. There is never a public or predictable URL
     * (DAT-FR-02, spec Section 12).
     */
    public function downloadResponse(): StreamedResponse
    {
        return Storage::disk($this->disk)->download($this->path, $this->original_name);
    }

    /**
     * @return BelongsTo<VerificationRequest, $this>
     */
    public function verificationRequest(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
