<?php

namespace Modules\Inquiries\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Inquiries\Enums\LeadStatus;

/**
 * `PATCH /api/v1/inquiries/{inquiry}` — the seller moves a lead through the
 * four fixed statuses (US-INQ-06/07). No other values are accepted and no
 * ordering between them is enforced.
 */
class UpdateInquiryLeadStatusRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_status' => ['required', Rule::enum(LeadStatus::class)],
        ];
    }
}
