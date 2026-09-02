<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Support\PhoneNumber;
use Modules\Core\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Canonical phone, or null when the supplied value is not a recognizable
     * Egyptian mobile number (treated as a failed login, not a validation error).
     */
    public function phone(): ?string
    {
        return PhoneNumber::normalize($this->input('phone'));
    }

    public function throttleKey(): string
    {
        return 'auth:login:'.($this->phone() ?? 'invalid').'|'.$this->ip();
    }
}
