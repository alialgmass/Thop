<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Raised when an OTP cannot be verified. Renders as a 422 with a single,
 * localized message so the client can tell the user what went wrong.
 */
class InvalidOtpException extends ExceptionResponse
{
    public static function noActiveRequest(): static
    {
        return static::instance(__('auth::otp.no_active_request'), 422)
            ->setCustomCode(4221)
            ->setCustomBody(['code' => [__('auth::otp.no_active_request')]]);
    }

    public static function expired(): static
    {
        return static::instance(__('auth::otp.expired'), 422)
            ->setCustomCode(4221)
            ->setCustomBody(['code' => [__('auth::otp.expired')]]);
    }

    public static function locked(): static
    {
        return static::instance(__('auth::otp.locked'), 422)
            ->setCustomCode(4221)
            ->setCustomBody(['code' => [__('auth::otp.locked')]]);
    }

    public static function mismatch(): static
    {
        return static::instance(__('auth::otp.mismatch'), 422)
            ->setCustomCode(4221)
            ->setCustomBody(['code' => [__('auth::otp.mismatch')]]);
    }
}
