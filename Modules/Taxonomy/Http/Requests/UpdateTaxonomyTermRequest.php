<?php

namespace Modules\Taxonomy\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class UpdateTaxonomyTermRequest extends BaseRequest
{
    /**
     * `hex` only has an effect on `colors` — see {@see StoreTaxonomyTermRequest}.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'hex' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
