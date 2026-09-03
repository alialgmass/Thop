<?php

namespace Modules\Search\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Core\Http\Requests\BaseRequest;

/**
 * Query contract for supplier search (US-SRC-07).
 * `GET /api/v1/businesses?search=&filters[governorate_id|verification_status|specialty]=`
 */
class SupplierSearchRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],

            'filters' => ['nullable', 'array'],
            'filters.governorate_id' => ['nullable', 'integer'],
            'filters.verification_status' => ['nullable', Rule::in(array_column(VerificationStatus::cases(), 'value'))],
            'filters.specialty' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchParams(): array
    {
        return [
            'search' => $this->input('search'),
            'per_page' => $this->input('per_page'),
            'filters' => (array) $this->input('filters', []),
        ];
    }
}
