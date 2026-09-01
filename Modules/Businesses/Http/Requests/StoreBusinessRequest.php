<?php

namespace Modules\Businesses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Businesses\Http\Requests\Concerns\BusinessProfileRules;

class StoreBusinessRequest extends FormRequest
{
    use BusinessProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->profileRules(partial: false);
    }
}
