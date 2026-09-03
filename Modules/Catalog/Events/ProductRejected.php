<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;

/**
 * Fired when an admin rejects a product with a reason (US-SEL-11). No listeners
 * in Phase 3; Notifications wiring arrives in Phase 8.
 */
class ProductRejected
{
    use Dispatchable;

    public function __construct(
        public readonly Product $product,
        public readonly string $reason,
    ) {}
}
