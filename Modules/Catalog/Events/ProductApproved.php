<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;

/**
 * Fired when an admin approves a product (moves it to published). No listeners
 * in Phase 3; Notifications wiring arrives in Phase 8.
 */
class ProductApproved
{
    use Dispatchable;

    public function __construct(public readonly Product $product) {}
}
