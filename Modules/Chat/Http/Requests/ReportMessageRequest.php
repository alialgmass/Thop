<?php

namespace Modules\Chat\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/conversations/{conversation}/messages/{message}/reports` —
 * either party flags a message as abusive (US-CHT-09).
 */
class ReportMessageRequest extends BaseRequest
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
