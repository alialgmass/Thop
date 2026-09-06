<?php

namespace Modules\Catalog\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/products/{product}/media` — one image per request (US-SEL-03).
 * Validated by extension AND content type AND size before it is stored, so a
 * disguised file is rejected and nothing is written.
 */
class StoreProductMediaRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', config('catalog.media.accepted_mimes')),
                'mimetypes:'.implode(',', config('catalog.media.accepted_mimetypes')),
                'max:'.(int) config('catalog.media.max_file_size_kb'),
            ],
        ];
    }
}
