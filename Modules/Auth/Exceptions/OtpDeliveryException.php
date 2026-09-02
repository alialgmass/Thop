<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when the SMS/OTP provider could not accept an outbound message.
 */
class OtpDeliveryException extends ExceptionResponse
{
    protected $code = 503;
}
