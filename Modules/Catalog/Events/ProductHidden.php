<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;

/**
 * Fired when an admin hides an already-published product. No listeners in
 * Phase 3; Notifications wiring arrives in Phase 8.
 */
class ProductHidden
{
    use Dispatchable;

    public function __construct(public readonly Product $product) {}
}
