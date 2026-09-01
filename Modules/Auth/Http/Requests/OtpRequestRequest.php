<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Http\Requests\Concerns\NormalizesPhone;
use Modules\Auth\Rules\EgyptianMobile;

class OtpRequestRequest extends FormRequest
{
    use NormalizesPhone;

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
            'phone' => ['required', 'string', new EgyptianMobile],
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
        ];
    }

    public function purpose(): OtpPurpose
    {
        return OtpPurpose::from($this->input('purpose'));
    }
}
