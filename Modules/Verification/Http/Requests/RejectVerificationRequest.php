<?php

namespace Modules\Verification\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class RejectVerificationRequest extends BaseRequest
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
