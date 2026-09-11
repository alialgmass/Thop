<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;

/**
 * Fired when an admin sends a pending-review product back to the seller for
 * edits (US-SEL-11 "request edits") — the product returns to draft rather
 * than being rejected outright. No listeners in Phase 9·T2; Notifications
 * wiring reuses ProductRejected's channel config until this event needs its
 * own (Phase 8's matrix has no separate "edits requested" row yet).
 */
class ProductEditsRequested
{
    use Dispatchable;

    public function __construct(
        public readonly Product $product,
        public readonly string $reason,
    ) {}
}
