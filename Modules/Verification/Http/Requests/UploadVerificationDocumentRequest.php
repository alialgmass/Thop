<?php

namespace Modules\Verification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a single verification document (MIME + size) BEFORE it is written
 * anywhere (SEC-NFR-05).
 */
class UploadVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')],
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('verification.accepted_mimes', ['pdf', 'jpg', 'jpeg', 'png'])),
                'max:'.(int) config('verification.max_file_size_kb', 10240),
            ],
        ];
    }
}
