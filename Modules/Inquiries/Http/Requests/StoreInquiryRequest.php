<?php

namespace Modules\Inquiries\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/inquiries` — contact a seller from a product page or a
 * supplier profile (US-INQ-01). At least one of `seller_business_id` /
 * `product_id` must be given; the controller resolves the seller from the
 * product when only `product_id` is supplied, and rejects a mismatch when
 * both are given.
 */
class StoreInquiryRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'seller_business_id' => ['required_without:product_id', 'nullable', 'integer', 'exists:business_accounts,id'],
            'product_id' => ['required_without:seller_business_id', 'nullable', 'integer', 'exists:products,id'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
