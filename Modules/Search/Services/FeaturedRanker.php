<?php

namespace Modules\Search\Services;

use Illuminate\Support\Collection;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * Applies the featured ranking boost (US-SRC-10, BR-SRC-01).
 *
 * The boost is a bounded positional adjustment: a featured row moves up by at
 * most {@see self::BOOST_POSITIONS} places within the already-fetched page. It
 * never removes a non-featured row and never lets a featured row jump an
 * arbitrary distance — a much higher-relevance organic row still wins. Whether
 * a business is featured is resolved server-side from its active subscription
 * every request (a lapsed/downgraded plan loses the boost with no code change),
 * and every row is tagged with a truthful `featured` flag so the client can
 * label it.
 */
class FeaturedRanker
{
    /** Maximum places a featured row may climb within a page. */
    public const BOOST_POSITIONS = 12;

    public function __construct(private EntitlementService $entitlements) {}

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $items  in base-sort order
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

        return $items
            ->values()
            ->sortBy(
                fn ($item, int $position): int => $position - ($item->featured ? self::BOOST_POSITIONS : 0),
                SORT_NUMERIC,
            )
            ->values();
    }
}
