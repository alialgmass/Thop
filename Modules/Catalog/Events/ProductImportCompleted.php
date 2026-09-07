<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\ProductImportBatch;

/**
 * Fired when a bulk product import finishes — completed or failed (US-SEL-09,
 * "processing happens as a queued Job with a progress/result notification").
 * No listeners in Phase 3; the seller notification is wired in Phase 8. Until
 * then the seller polls `GET /products/import/{id}` for the per-row report.
 */
class ProductImportCompleted
{
    use Dispatchable;

    public function __construct(public readonly ProductImportBatch $batch) {}
}
