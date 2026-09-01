<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Support\HandoffToken;

class PasswordResetRequest extends FormRequest
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
