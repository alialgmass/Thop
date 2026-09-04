<?php

namespace Modules\Search\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Search\Support\FullTextMatch;
use Modules\Search\Support\PerPage;
use Modules\Search\Support\SearchNormalizer;

/**
 * Supplier discovery (US-SRC-07): find business accounts by governorate,
 * verification status and specialty, with optional free text over company
 * name and activity. Customer users have no business account, so they can
 * never appear here. Featured supplier profiles get the ranking boost.
 */
class SupplierSearchService
{
    public function __construct(
        private SearchNormalizer $normalizer,
        private FullTextMatch $fullText,
        private FeaturedRanker $ranker,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function search(array $params): LengthAwarePaginator
    {
        $term = $this->normalizer->normalize($params['search'] ?? null);
        $filters = $params['filters'] ?? [];

        $query = BusinessAccount::query()
            ->with(['governorate'])
            ->activeAccount();

        if (! empty($filters['governorate_id'])) {
            $query->where('governorate_id', $filters['governorate_id']);
        }

        if (! empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (! empty($filters['specialty'])) {
            $this->fullText->constrain($query, 'search_text', $this->normalizer->normalize($filters['specialty']));
        }

        if ($term !== '') {
            $this->fullText->constrain($query, 'search_text', $term);
        }

        $query->orderBy('company_name')->orderByDesc('id');

        $paginator = $query->paginate(PerPage::resolve($params['per_page'] ?? null))->withQueryString();

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
}
