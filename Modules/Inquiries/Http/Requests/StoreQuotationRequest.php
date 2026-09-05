<?php

namespace Modules\Inquiries\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/rfqs/{rfq}/quotations` — the seller's time-bound reply
 * (US-INQ-03).
 */
class StoreQuotationRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'availability_note' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['required', 'date'],
        ];
    }
}
