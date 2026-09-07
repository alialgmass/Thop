<?php

namespace Modules\Catalog\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * The bulk product import upload (Phase 3.3). CSV only for now — an XLSX reader
 * needs dependency sign-off on the issue before it can be accepted here.
 */
class StoreProductImportRequest extends BaseRequest
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
                'mimes:csv,txt',
                'max:2048',
            ],
        ];
    }
}
