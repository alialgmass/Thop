<?php

namespace Modules\Auth\Exceptions;

use Illuminate\Support\Facades\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Raised when an OTP cannot be verified. Renders as a 422 with a single,
 * localized message so the client can tell the user what went wrong.
 */
class InvalidOtpException extends RuntimeException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function noActiveRequest(): self
    {
        return new self('no_active_request', __('auth::otp.no_active_request'));
    }

    public static function expired(): self
    {
        return new self('expired', __('auth::otp.expired'));
    }

    public static function locked(): self
    {
        return new self('locked', __('auth::otp.locked'));
    }

    public static function mismatch(): self
    {
        return new self('mismatch', __('auth::otp.mismatch'));
    }

    public function render(): SymfonyResponse
    {
        return Response::json([
            'message' => $this->getMessage(),
            'errors' => ['code' => [$this->getMessage()]],
        ], 422);
    }
}
