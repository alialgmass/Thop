<?php

namespace Modules\Catalog\Actions;

use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Exceptions\ProductLimitExceededException;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Support\CatalogGating;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * Shared create logic for a product, used by the single-create endpoint AND by
 * each row of a bulk import so both paths stay consistent (US-SEL-09, US-SEL-10).
 *
 * The product-count limit is always enforced server-side against the active
 * subscription via EntitlementService (BR-SEL-01, SEC-NFR-04) — never from any
 * client claim. On success the usage counter is incremented; a restricted
 * subscription blocks creation entirely.
 */
class CreateProduct
{
    public function __construct(
        private readonly EntitlementService $entitlements,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $colorIds
     * @param  list<array{min_qty: int, unit_price: float}>  $priceTiers
     */
    public function create(
        BusinessAccount $business,
        array $attributes,
        array $colorIds = [],
        array $priceTiers = [],
        bool $explicitDraft = false,
    ): Product {
        if (! $this->entitlements->can($business, 'product_limit')) {
            throw new ProductLimitExceededException;
        }

        $status = $explicitDraft
            ? ProductStatus::Draft
            : (CatalogGating::requiresReviewOnCreate()
                ? ProductStatus::PendingReview
                : ProductStatus::Published);

        $product = $business->products()->create(array_merge($attributes, [
            'business_account_id' => $business->getKey(),
            'status' => $status,
        ]));

        if ($colorIds !== []) {
            $product->colors()->attach($colorIds);
        }

        if ($priceTiers !== []) {
            $product->priceTiers()->createMany($priceTiers);
        }

        $this->entitlements->incrementUsage($business, 'product_count');

        return $product;
    }
}
