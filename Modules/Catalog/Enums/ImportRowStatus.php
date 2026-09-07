<?php

namespace Modules\Catalog\Enums;

/**
 * The outcome of a single data row in a bulk import (Phase 3.3, spec §4.2 —
 * "a bad row doesn't stop the file").
 *
 * - Imported: a product was created and entered pending_review.
 * - Failed: the row failed field validation (price XOR, missing taxonomy, …).
 * - LimitRejected: the row was valid but the plan product_limit (BR-SEL-01) was
 *   already reached, so nothing was created for it.
 */
enum ImportRowStatus: string
{
    case Imported = 'imported';
    case Failed = 'failed';
    case LimitRejected = 'limit_rejected';
}
