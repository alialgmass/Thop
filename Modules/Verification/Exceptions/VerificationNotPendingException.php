<?php

namespace Modules\Verification\Exceptions;

use RuntimeException;

/**
 * Thrown when an approve/reject is attempted on a verification request that is
 * not awaiting review (US-ADM-01 — "Given a *pending* verification request").
 */
class VerificationNotPendingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This verification request is not awaiting review.');
    }
}
