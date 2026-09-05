<?php

namespace Modules\Inquiries\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Inquiries\Models\Inquiry;

/**
 * Fired when a buyer sends an inquiry. No listeners exist yet; the
 * Notifications module (Phase 8) attaches `new_inquiry` (NOT-FR-01) per the
 * Notification Matrix (spec Section 14) without touching this module.
 */
class InquiryCreated
{
    use Dispatchable;

    public function __construct(public readonly Inquiry $inquiry) {}
}
