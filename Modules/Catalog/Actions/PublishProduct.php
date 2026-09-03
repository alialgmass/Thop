<?php

namespace Modules\Catalog\Actions;

use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Exceptions\ProductLimitExceededException;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Support\CatalogGating;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * The single gate a product must pass before becoming publicly visible
 * (US-SEL-03, BR-SEL-01, BR-SEL-03). It validates:
 *   - at least one image is attached (US-SEL-03),
 *   - the price XOR rule holds (BR-SEL-03),
 *   - the business is within its plan product limit (BR-SEL-01).
 * On success it moves the product to pending_review (when review is ON) or to
 * published. Approve/reject decisions happen in {@see DecideProductReview}.
 */
class PublishProduct
{
    public function __construct(
        private readonly EntitlementService $entitlements,
    ) {}

    public function publish(Product $product): Product
    {
        if (! $product->hasImage()) {
            throw ExceptionResponse::instance(__('catalog::messages.media_required_to_publish'), 400)
                ->setCustomBody(['media' => [__('catalog::messages.media_required_to_publish')]]);
        }

        $product->ensureValidPricing();

        $business = $product->businessAccount;

        if (! $this->entitlements->can($business, 'product_limit')) {
            throw new ProductLimitExceededException;
        }

        $product->forceFill([
            'status' => CatalogGating::requiresReviewOnCreate()
                ? ProductStatus::PendingReview
                : ProductStatus::Published,
            'rejection_reason' => null,
        ])->save();

        return $product;
    }
}