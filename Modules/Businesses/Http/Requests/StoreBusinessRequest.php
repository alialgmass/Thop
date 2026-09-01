<?php

namespace Modules\Businesses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'activity' => ['required', 'string', 'max:255'],
            'governorate_id' => ['required', 'integer', Rule::exists('governorates', 'id')],
            'address' => ['required', 'string', 'max:500'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_channels' => ['nullable', 'array'],
            'contact_channels.*.type' => ['required_with:contact_channels', 'string', 'max:50'],
            'contact_channels.*.value' => ['required_with:contact_channels', 'string', 'max:255'],
        ];
    }
}
