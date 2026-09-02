<?php

namespace Modules\Verification\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an approve/reject is attempted on a verification request that is
 * not awaiting review (US-ADM-01 — "Given a *pending* verification request").
 * Rendered by the Core handler as a 409 envelope (custom code 4092).
 */
class VerificationNotPendingException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('verification::messages.not_pending');

        parent::__construct($message, 409);

        $this->setCustomCode(4092)
            ->setCustomBody(['status' => [$message]]);
    }
}
