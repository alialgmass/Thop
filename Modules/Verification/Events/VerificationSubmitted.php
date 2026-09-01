<?php

namespace Modules\Verification\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Verification\Models\VerificationRequest;

/**
 * Fired when a business submits its documents for review. No listeners exist in
 * Phase 1; the Notifications module (Phase 8) attaches them per the Notification
 * Matrix (spec Section 14) without touching this module.
 */
class VerificationSubmitted
{
    use Dispatchable;

    public function __construct(public readonly VerificationRequest $verificationRequest) {}
}
