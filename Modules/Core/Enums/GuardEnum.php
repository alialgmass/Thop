<?php

namespace Modules\Core\Enums;

use Illuminate\Contracts\Auth\Authenticatable;

enum GuardEnum: string
{
    case adminSessions = 'admin_sessions';
    case admins = 'admins';
    case userSessions = 'user_sessions';
    case users = 'sanctum';
    case OTPVerification = 'otp_verifications';
    case whatsappSessions = 'whatsapp_sessions';

    public function middleware(): string
    {
        return 'auth:'.$this->value;
    }

    public function label(): string
    {
        return __("enums.billing-period.{$this->value}");
    }

    public function authUser(): ?Authenticatable
    {
        return auth($this->value)->user();
    }

    public static function toArray(): array
    {
        $array = [];

        foreach (self::cases() as $definition) {
            $array[$definition->value] = $definition->value;
        }

        return $array;
    }
}
