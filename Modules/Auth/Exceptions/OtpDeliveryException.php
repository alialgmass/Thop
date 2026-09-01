<?php

namespace Modules\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when the SMS/OTP provider could not accept an outbound message.
 */
class OtpDeliveryException extends RuntimeException {}
