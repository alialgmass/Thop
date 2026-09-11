<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Models\Inquiry;
use Modules\Search\Models\SearchLog;

/**
 * Marketplace-health read service for the admin liquidity dashboard
 * (US-ADM-06, Phase 9 · T7, issue #36). Read-only — no new table, every
 * number is derived from existing rows every call.
 *
 * "Active sellers" / "active products" are current-state snapshots (not
 * time-windowed — a seller with a published product today is active today,
 * regardless of when it was published). "Active buyers", "inquiries" and
 * "zero-result terms" are windowed by `$days` (default 7, per the ticket's
 * own "last 7 days" wording).
 */
class LiquidityMetrics
{
    /**
     * How many zero-result terms to return. The ticket says "top N" without
     * naming N — Implementation Assumption, not specified in the spec.
     */
    private const TOP_ZERO_RESULT_TERMS = 10;

    /**
     * @return array{active_sellers: int, active_products: int, active_buyers: int, inquiries_last_period: int, zero_result_terms: list<array{term: string, count: int}>, range_days: int}
     */
    public function summarize(int $days = 7): array
    {
        $since = Carbon::now()->subDays($days);

        return [
            'active_sellers' => $this->activeSellers(),
            'active_products' => $this->activeProducts(),
            'active_buyers' => $this->activeBuyers($since),
            'inquiries_last_period' => $this->inquiriesSince($since),
            'zero_result_terms' => $this->zeroResultTerms($since),
            'range_days' => $days,
        ];
    }

    /**
     * A seller with at least one published, buyer-visible product — reuses
     * the exact same visibility gate buyers see (BR-SRC-02, BR-SUB-03), so
     * this can never count a seller whose catalog is actually hidden.
     */
    private function activeSellers(): int
    {
        return Product::query()->buyerVisible()->distinct('business_account_id')->count('business_account_id');
    }

    private function activeProducts(): int
    {
        return Product::query()->buyerVisible()->count();
    }

    private function activeBuyers(Carbon $since): int
    {
        return Inquiry::query()->where('created_at', '>=', $since)->distinct('buyer_id')->count('buyer_id');
    }

    private function inquiriesSince(Carbon $since): int
    {
        return Inquiry::query()->where('created_at', '>=', $since)->count();
    }

    /**
     * Grouped by `normalized_term` — never the raw `user_id` (no PII).
     *
     * @return list<array{term: string, count: int}>
     */
    private function zeroResultTerms(Carbon $since): array
    {
        return SearchLog::query()
            ->selectRaw('normalized_term, count(*) as term_count')
            ->where('created_at', '>=', $since)
            ->groupBy('normalized_term')
            ->orderByDesc('term_count')
            ->limit(self::TOP_ZERO_RESULT_TERMS)
            ->get()
            ->map(fn ($row): array => ['term' => $row->normalized_term, 'count' => (int) $row->term_count])
            ->all();
    }
}
