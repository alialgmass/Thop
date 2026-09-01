<?php

namespace Modules\Verification\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Verification\Models\VerificationRequest;

/**
 * Fired when an admin rejects a verification request. No listeners in Phase 1
 * (see {@see VerificationSubmitted}).
 */
class VerificationRejected
{
    use Dispatchable;

    public function __construct(
        public readonly VerificationRequest $verificationRequest,
        public readonly string $reason,
    ) {}
}
