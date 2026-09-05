<?php

namespace Modules\Inquiries\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/inquiries/{inquiry}/reports` — either party flags an inquiry
 * as abusive (US-INQ-09).
 */
class StoreReportRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
