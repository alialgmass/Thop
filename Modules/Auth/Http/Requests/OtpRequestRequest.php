<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Rules\EgyptianMobile;
use Modules\Auth\Support\PhoneNumber;

class OtpRequestRequest extends FormRequest
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
            'phone' => ['required', 'string', new EgyptianMobile],
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
        ];
    }

    public function phone(): string
    {
        return PhoneNumber::normalize($this->input('phone'));
    }

    public function purpose(): OtpPurpose
    {
        return OtpPurpose::from($this->input('purpose'));
    }
}
