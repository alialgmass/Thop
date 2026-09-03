<?php

namespace Modules\Search\Observers;

use Modules\Businesses\Models\BusinessAccount;
use Modules\Search\Support\SearchNormalizer;

/**
 * Maintains {@see BusinessAccount::$search_text} for supplier free-text search
 * (US-SRC-07) — the normalized company name and activity.
 */
class BusinessAccountSearchIndexer
{
    public function __construct(private SearchNormalizer $normalizer) {}

    public function saving(BusinessAccount $business): void
    {
        if ($business->exists && ! $business->isDirty(['company_name', 'activity'])) {
            return;
        }

        $business->search_text = $this->normalizer->normalize(
            trim(implode(' ', array_filter([$business->company_name, $business->activity]))),
        );
    }
}
