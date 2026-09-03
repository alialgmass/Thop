<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;

/**
 * Seller-initiated status transitions (US-SEL-07, US-SEL-08): hide, unhide,
 * mark unavailable, or submit-for-publish. Approve/reject stay admin-only and
 * go through DecideProductReview.
 */
class UpdateProductStatusRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'hidden',
                'unavailable',
                'published',
                'pending_review',
            ])],
        ];
    }
}
