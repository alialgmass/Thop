<?php

namespace Modules\Inquiries\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/inquiries/{inquiry}/rfqs` — a structured request-for-quotation
 * (US-INQ-02). Below-MOQ quantity is a warning, not a validation failure —
 * enforced separately in the controller, not here.
 */
class StoreRfqRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'needed_by_date' => ['required', 'date'],
        ];
    }
}
