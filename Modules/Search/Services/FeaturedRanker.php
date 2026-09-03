<?php

namespace Modules\Search\Services;

use Illuminate\Support\Collection;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * Applies the featured ranking boost (US-SRC-10, BR-SRC-01).
 *
 * The boost is deliberately bounded: it is a stable partition of the already
 * fetched page — featured items move ahead of non-featured items *within the
 * page*, never removing a non-featured item and never pulling an item across
 * page boundaries. Whether a business is featured is resolved server-side from
 * its active subscription every request (a lapsed/downgraded plan loses the
 * boost with no code change), and every row is tagged with a truthful
 * `featured` flag so the client can label it.
 */
class FeaturedRanker
{
    public function __construct(private EntitlementService $entitlements) {}

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $items
     * @param  bool  $applyBoost  re-order within the page (false for price sorts — flag only)
     * @param  (callable(TModel): ?BusinessAccount)|null  $businessResolver
     * @return Collection<int, TModel>
     */
    public function rank(Collection $items, string $entitlementKey, bool $applyBoost, ?callable $businessResolver = null): Collection
    {
        $businessResolver ??= fn ($item) => $item->businessAccount;
        $cache = [];

        $items->each(function ($item) use (&$cache, $entitlementKey, $businessResolver): void {
            $business = $businessResolver($item);
            $businessId = $business?->getKey();

            if ($businessId === null) {
                $item->featured = false;

                return;
            }

            $cache[$businessId] ??= $this->entitlements->can($business, $entitlementKey);
            $item->featured = $cache[$businessId];
        });

        if (! $applyBoost) {
            return $items;
        }

        // PHP 8's sort is stable, so non-featured relative order is preserved.
        return $items->sortByDesc(fn ($item): bool => (bool) $item->featured)->values();
    }
}
