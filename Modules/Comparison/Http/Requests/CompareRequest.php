<?php

namespace Modules\Comparison\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Favorites\Support\Favoritable;

/**
 * `GET /api/v1/compare?type=product|supplier&ids=1,2,3,4`
 *
 * BR-CMP-01: comparison is capped at 4 items — a 5th is rejected with a clear
 * validation error, not silently dropped.
 */
class CompareRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $ids = $this->query('ids');

        if (is_string($ids)) {
            $this->merge([
                'ids' => array_values(array_filter(array_map('trim', explode(',', $ids)), fn ($v) => $v !== '')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Favoritable::types())],
            'ids' => ['required', 'array', 'min:1', 'max:4'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.max' => __('comparison::messages.too_many'),
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_map('intval', $this->input('ids', []));
    }
}
