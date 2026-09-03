<?php

namespace Modules\Catalog\Enums;

/**
 * The lifecycle of a product listing (§10.3). New products start as draft;
 * submission moves them toward pending_review (gated by admin review config)
 * or directly toward published. Only {@see self::Published} is visible to
 * buyers in default search; {@see self::Unavailable} drops out of search but
 * stays reachable by direct link (clearly labelled).
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Hidden = 'hidden';
    case Unavailable = 'unavailable';
    case Rejected = 'rejected';

    /**
     * The states a buyer must never see in any result (BR-SRC-02, anticipated).
     */
    public function isVisibleToBuyers(): bool
    {
        return $this === self::Published;
    }
}
