<?php

namespace Modules\Inquiries\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Inquiries\Models\Quotation;

/**
 * Fired when a seller replies to an RFQ. No listeners exist yet; the
 * Notifications module (Phase 8) attaches `quotation_received` (US-INQ-03)
 * without touching this module.
 */
class QuotationReceived
{
    use Dispatchable;

    public function __construct(public readonly Quotation $quotation) {}
}
