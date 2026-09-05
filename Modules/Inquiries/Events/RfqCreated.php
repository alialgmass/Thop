<?php

namespace Modules\Inquiries\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Inquiries\Models\Rfq;

/**
 * Fired when a buyer submits a structured RFQ. No listeners exist yet; the
 * Notifications module (Phase 8) attaches `new_rfq` (spec §4.4) without
 * touching this module.
 */
class RfqCreated
{
    use Dispatchable;

    public function __construct(public readonly Rfq $rfq) {}
}
