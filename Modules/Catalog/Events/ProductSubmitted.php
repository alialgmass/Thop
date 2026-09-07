<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;

/**
 * Fired when a product enters the review queue (`pending_review`) — on
 * create, on publish, or per row of a bulk import (US-SEL-02/11). The
 * Notifications module (Phase 8) attaches `product_submitted` (§14) for the
 * admin queue; no listeners live here.
 */
class ProductSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Product $product) {}
}
