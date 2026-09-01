<?php

namespace Modules\Auth\Contracts;

use Modules\Auth\Exceptions\OtpDeliveryException;

/**
 * Outbound boundary for delivering a one-time password to a phone number.
 *
 * Implementations must never persist or log the plaintext code. A delivery
 * failure is signalled by throwing {@see OtpDeliveryException} so the caller
 * can surface a localized, actionable error instead of a generic 500.
 */
interface OtpSender
{
    /**
     * @throws OtpDeliveryException when the provider could not accept the message
     */
    public function send(string $phone, string $code): void;
}
