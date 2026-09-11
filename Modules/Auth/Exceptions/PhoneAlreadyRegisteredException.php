<?php

namespace Modules\Auth\Exceptions;

use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when a phone number is already registered — public registration
 * ({@see RegisterController}) and admin assisted onboarding
 * (`Modules\Admin\Actions\OnboardSupplier`, not imported here — Auth
 * doesn't depend on Admin, Admin depends on Auth) apply the exact same
 * duplicate-phone rule (Phase 9 · T10 acceptance criterion), so this is the
 * one place that rule is expressed.
 */
class PhoneAlreadyRegisteredException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('auth::otp.already_registered');

        parent::__construct($message, 409);

        $this->setCustomCode(4091)
            ->setCustomBody(['phone' => [$message]]);
    }
}
