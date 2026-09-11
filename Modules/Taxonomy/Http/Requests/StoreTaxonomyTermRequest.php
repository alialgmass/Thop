<?php

namespace Modules\Taxonomy\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;
use Modules\Taxonomy\Actions\ManageTaxonomyTerm;

class StoreTaxonomyTermRequest extends BaseRequest
{
    /**
     * `hex` only has an effect on `colors` — {@see ManageTaxonomyTerm}
     * silently drops it for every other type rather than rejecting it here,
     * so a generic client doesn't need to know which type it's posting to.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'hex' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
