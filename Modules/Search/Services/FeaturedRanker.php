<?php

namespace Modules\Search\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Search\Models\FeaturedPlacement;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * Applies the featured ranking boost (US-SRC-10, BR-SRC-01).
 *
 * The boost is a bounded positional adjustment: a featured row moves up by at
 * most {@see self::BOOST_POSITIONS} places within the already-fetched page. It
 * never removes a non-featured row and never lets a featured row jump an
 * arbitrary distance — a much higher-relevance organic row still wins. The
 * truthful `featured` flag is a union of two independent things (Phase 9 · T5):
 * an active admin-curated {@see FeaturedPlacement} for that exact item, OR its
 * active subscription granting the entitlement key — either one is enough,
 * resolved server-side every request (a lapsed plan or an expired placement
 * both lose the boost with no code change).
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
        $placedIds = $this->activePlacementIds($items);

        $items->each(function ($item) use (&$cache, $entitlementKey, $businessResolver, $placedIds): void {
            if (in_array($item->getKey(), $placedIds, true)) {
                $item->featured = true;

                return;
            }

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

    /**
     * One query per rank() call: every item in a page is the same model
     * class, so this is a single `whereIn` against the placements table
     * rather than a query per item.
     *
     * @param  Collection<int, Model>  $items
     * @return list<int>
     */
    private function activePlacementIds(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        return FeaturedPlacement::query()
            ->where('featurable_type', $items->first()->getMorphClass())
            ->whereIn('featurable_id', $items->map->getKey())
            ->active()
            ->pluck('featurable_id')
            ->all();
    }
}
