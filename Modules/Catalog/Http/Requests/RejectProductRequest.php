<?php

namespace Modules\Catalog\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class RejectProductRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
