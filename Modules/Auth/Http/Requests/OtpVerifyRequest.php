<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Http\Requests\Concerns\NormalizesPhone;
use Modules\Auth\Rules\EgyptianMobile;
use Modules\Core\Http\Requests\BaseRequest;

class OtpVerifyRequest extends BaseRequest
{
    use NormalizesPhone;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new EgyptianMobile],
            'code' => ['required', 'string'],
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
        ];
    }

    public function purpose(): OtpPurpose
    {
        return OtpPurpose::from($this->input('purpose'));
    }
}
