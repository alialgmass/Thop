<?php

namespace Modules\Search\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Search\Support\SearchNormalizer;

/**
 * MySQL-backed product discovery (US-SRC-01..04, §13). This is the one seam a
 * future dedicated engine (Meilisearch/OpenSearch) would replace — controllers
 * only pass validated params in and get a paginator out.
 *
 * Free-text matching branches on the driver: MySQL uses the FULLTEXT index on
 * `search_text`; SQLite (tests) falls back to LIKE over the same normalized
 * column. Both sides compare {@see SearchNormalizer}-normalized text so common
 * Arabic/English spelling variants still match.
 */
class ProductSearchService
{
    private const SORTS = ['relevance', 'price_asc', 'price_desc', 'newest', 'supplier_rating'];

    private const MAX_PER_PAGE = 50;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private SearchNormalizer $normalizer,
        private FeaturedRanker $ranker,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function search(array $params, ?int $businessAccountId = null): LengthAwarePaginator
    {
        $term = $this->normalizer->normalize($params['search'] ?? null);
        $hasTerm = $term !== '';

        $query = Product::query()
            ->buyerVisible()
            ->with(['fabricType', 'material', 'governorate', 'colors', 'media', 'priceTiers', 'businessAccount']);

        if ($businessAccountId !== null) {
            $query->where('business_account_id', $businessAccountId);
        }

        $this->applyFilters($query, $params['filters'] ?? []);

        if ($hasTerm) {
            $this->applyFreeText($query, $term);
        }

        $sort = $this->resolveSort($params['sort'] ?? null, $hasTerm);
        $this->applySort($query, $sort, $term);

        $perPage = $this->resolvePerPage($params['per_page'] ?? null);

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->setCollection(
            $this->ranker->rank(
                $paginator->getCollection(),
                'featured_products',
                applyBoost: in_array($sort, ['relevance', 'newest'], true),
            ),
        );

        return $paginator;
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['fabric_type_id', 'material_id', 'governorate_id', 'business_account_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }

        if (! empty($filters['color_id'])) {
            $colorIds = array_filter((array) $filters['color_id']);
            $query->whereHas('colors', fn ($c) => $c->whereIn('colors.id', $colorIds));
        }

        $this->applyRange($query, 'width_cm', $filters['width_cm_min'] ?? null, $filters['width_cm_max'] ?? null);
        $this->applyRange($query, 'price', $filters['price_min'] ?? null, $filters['price_max'] ?? null);

        if (isset($filters['availability'])) {
            $available = filter_var($filters['availability'], FILTER_VALIDATE_BOOLEAN);

            if ($available) {
                $query->where('quantity_available', '>', 0);
            }
        }

        if (isset($filters['moq_max']) && $filters['moq_max'] !== '') {
            $query->where(fn ($q) => $q->whereNull('moq')->orWhere('moq', '<=', (int) $filters['moq_max']));
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyRange(Builder $query, string $column, mixed $min, mixed $max): void
    {
        if ($min !== null && $min !== '') {
            $query->where($column, '>=', $min);
        }

        if ($max !== null && $max !== '') {
            $query->where($column, '<=', $max);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyFreeText(Builder $query, string $term): void
    {
        if (DB::getDriverName() === 'mysql') {
            $query->whereRaw('MATCH(search_text) AGAINST (? IN BOOLEAN MODE)', [$this->booleanTerm($term)]);

            return;
        }

        // SQLite / other: AND of per-token LIKE over the normalized column.
        foreach (explode(' ', $term) as $token) {
            $query->where('search_text', 'like', '%'.$this->escapeLike($token).'%');
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort, string $term): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('price IS NULL')->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByRaw('price IS NULL')->orderByDesc('price')->orderByDesc('id'),
            'newest' => $query->orderByDesc('products.created_at')->orderByDesc('id'),
            'supplier_rating' => $this->applySupplierRatingSort($query),
            default => $this->applyRelevanceSort($query, $term),
        };
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyRelevanceSort(Builder $query, string $term): void
    {
        if ($term === '') {
            $query->orderByDesc('products.created_at')->orderByDesc('id');

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $query->orderByRaw('MATCH(search_text) AGAINST (? IN BOOLEAN MODE) DESC', [$this->booleanTerm($term)])
                ->orderByDesc('id');

            return;
        }

        // SQLite: prefix hits first, then newest.
        $query->orderByRaw('CASE WHEN search_text LIKE ? THEN 0 ELSE 1 END', [$this->escapeLike($term).'%'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * No supplier-rating feature exists in R1 (no ratings table) — degrade to
     * "verified suppliers first, then newest". Recorded as an Implementation
     * Assumption.
     *
     * @param  Builder<Product>  $query
     */
    private function applySupplierRatingSort(Builder $query): void
    {
        $query->orderByDesc(
            DB::table('business_accounts')
                ->selectRaw("verification_status = 'verified'")
                ->whereColumn('business_accounts.id', 'products.business_account_id'),
        )->orderByDesc('products.created_at')->orderByDesc('products.id');
    }

    private function resolveSort(mixed $sort, bool $hasTerm): string
    {
        if (is_string($sort) && in_array($sort, self::SORTS, true)) {
            return $sort;
        }

        return $hasTerm ? 'relevance' : 'newest';
    }

    private function resolvePerPage(mixed $perPage): int
    {
        $value = (int) $perPage;

        if ($value < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($value, self::MAX_PER_PAGE);
    }

    /**
     * Turn a normalized term into a safe FULLTEXT boolean-mode expression:
     * every token becomes a required prefix match, operators are stripped.
     */
    private function booleanTerm(string $term): string
    {
        $tokens = array_filter(explode(' ', preg_replace('/[+\-><()~*"@]+/', ' ', $term) ?? $term));

        return implode(' ', array_map(fn (string $t): string => '+'.$t.'*', $tokens));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
