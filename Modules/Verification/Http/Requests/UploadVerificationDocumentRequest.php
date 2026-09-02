<?php

namespace Modules\Verification\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;

/**
 * Validates a single verification document BEFORE it is written anywhere
 * (SEC-NFR-05). The file is checked both by extension (`mimes`) and by sniffed
 * media type (`mimetypes`) so a renamed executable cannot slip through.
 */
class UploadVerificationDocumentRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => [
                'required',
                'integer',
                Rule::exists('document_types', 'id')->where('is_active', true),
            ],
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('verification.accepted_mimes')),
                'mimetypes:'.implode(',', config('verification.accepted_mimetypes')),
                'max:'.(int) config('verification.max_file_size_kb'),
            ],
        ];
    }
}
