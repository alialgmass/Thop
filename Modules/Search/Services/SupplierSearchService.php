<?php

namespace Modules\Search\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Search\Support\SearchNormalizer;

/**
 * Supplier discovery (US-SRC-07): find business accounts by governorate,
 * verification status and specialty, with optional free text over company
 * name and activity. Customer users have no business account, so they can
 * never appear here. Featured supplier profiles get the ranking boost.
 */
class SupplierSearchService
{
    private const MAX_PER_PAGE = 50;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private SearchNormalizer $normalizer,
        private FeaturedRanker $ranker,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function search(array $params): LengthAwarePaginator
    {
        $term = $this->normalizer->normalize($params['search'] ?? null);

        $query = BusinessAccount::query()
            ->with(['governorate'])
            ->whereHas('owner', fn ($owner) => $owner->where('status', '!=', 'suspended'));

        $filters = $params['filters'] ?? [];

        if (! empty($filters['governorate_id'])) {
            $query->where('governorate_id', $filters['governorate_id']);
        }

        if (! empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (! empty($filters['specialty'])) {
            $specialty = $this->normalizer->normalize($filters['specialty']);
            $query->where('search_text', 'like', '%'.$this->escapeLike($specialty).'%');
        }

        if ($term !== '') {
            $this->applyFreeText($query, $term);
        }

        $query->orderByDesc('company_name')->orderByDesc('id');

        $paginator = $query->paginate($this->resolvePerPage($params['per_page'] ?? null))->withQueryString();

        $paginator->setCollection(
            $this->ranker->rank(
                $paginator->getCollection(),
                'featured_supplier',
                applyBoost: true,
                businessResolver: fn (BusinessAccount $item): BusinessAccount => $item,
            ),
        );

        return $paginator;
    }

    /**
     * @param  Builder<BusinessAccount>  $query
     */
    private function applyFreeText(Builder $query, string $term): void
    {
        if (DB::getDriverName() === 'mysql') {
            $tokens = array_filter(explode(' ', preg_replace('/[+\-><()~*"@]+/', ' ', $term) ?? $term));
            $boolean = implode(' ', array_map(fn (string $t): string => '+'.$t.'*', $tokens));
            $query->whereRaw('MATCH(search_text) AGAINST (? IN BOOLEAN MODE)', [$boolean]);

            return;
        }

        foreach (explode(' ', $term) as $token) {
            $query->where('search_text', 'like', '%'.$this->escapeLike($token).'%');
        }
    }

    private function resolvePerPage(mixed $perPage): int
    {
        $value = (int) $perPage;

        return $value < 1 ? self::DEFAULT_PER_PAGE : min($value, self::MAX_PER_PAGE);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
