<?php

namespace Modules\Comparison\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;

/**
 * `GET /api/v1/compare?type=product|supplier&ids=1,2,3,4`
 *
 * BR-CMP-01: comparison is capped at 4 items — a 5th is rejected with a clear
 * validation error, not silently dropped. Repeated ids are de-duplicated.
 */
class CompareRequest extends BaseRequest
{
    /** The catalog entities that can be compared side-by-side (US-SRC-09). */
    public const TYPES = ['product', 'supplier'];

    protected function prepareForValidation(): void
    {
        $ids = $this->query('ids');

        if (is_string($ids)) {
            $parsed = array_filter(array_map('trim', explode(',', $ids)), fn ($value): bool => $value !== '');

            $this->merge(['ids' => array_values(array_unique($parsed))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(self::TYPES)],
            'ids' => ['required', 'array', 'min:1', 'max:4'],
            'ids.*' => ['integer', 'min:1'],
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
