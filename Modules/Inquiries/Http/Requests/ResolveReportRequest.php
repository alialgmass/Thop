<?php

namespace Modules\Inquiries\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Inquiries\Enums\ReportStatus;

class ResolveReportRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:2000'],
            'status' => ['sometimes', Rule::in([ReportStatus::Resolved->value, ReportStatus::Dismissed->value])],
        ];
    }
}
