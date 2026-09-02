<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rules\Password;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Support\HandoffToken;
use Modules\Core\Http\Requests\BaseRequest;

class PasswordResetRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::default()],
        ];
    }

    public function verifiedPhone(): ?string
    {
        return HandoffToken::verifiedPhone(
            $this->input('reset_token'),
            OtpPurpose::PasswordReset,
        );
    }
}
