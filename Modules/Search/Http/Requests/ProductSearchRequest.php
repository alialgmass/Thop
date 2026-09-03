<?php

namespace Modules\Search\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;

/**
 * Query contract for public product search (US-SRC-01..04).
 * `GET /api/v1/products?search=&filters[...]=&sort=&page=&per_page=`
 */
class ProductSearchRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['relevance', 'price_asc', 'price_desc', 'newest', 'supplier_rating'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],

            'filters' => ['nullable', 'array'],
            'filters.fabric_type_id' => ['nullable', 'integer'],
            'filters.material_id' => ['nullable', 'integer'],
            'filters.governorate_id' => ['nullable', 'integer'],
            'filters.business_account_id' => ['nullable', 'integer'],
            'filters.color_id' => ['nullable'],
            'filters.color_id.*' => ['integer'],
            'filters.width_cm_min' => ['nullable', 'integer', 'min:0'],
            'filters.width_cm_max' => ['nullable', 'integer', 'min:0'],
            'filters.price_min' => ['nullable', 'numeric', 'min:0'],
            'filters.price_max' => ['nullable', 'numeric', 'min:0'],
            'filters.availability' => ['nullable', 'boolean'],
            'filters.moq_max' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchParams(): array
    {
        return [
            'search' => $this->input('search'),
            'sort' => $this->input('sort'),
            'per_page' => $this->input('per_page'),
            'filters' => (array) $this->input('filters', []),
        ];
    }
}
