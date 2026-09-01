<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Support\HandoffToken;

class RegisterRequest extends FormRequest
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
            'registration_token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::default()],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'language' => ['nullable', 'in:ar,en'],
        ];
    }

    /**
     * The verified phone number carried by the registration token, or null when
     * the token is missing, tampered, or expired.
     */
    public function verifiedPhone(): ?string
    {
        return HandoffToken::verifiedPhone(
            $this->input('registration_token'),
            OtpPurpose::Registration,
        );
    }

    public function language(): string
    {
        return $this->input('language') ?: 'ar';
    }
}
