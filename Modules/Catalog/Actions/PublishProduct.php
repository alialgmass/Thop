<?php

namespace Modules\Catalog\Actions;

use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Support\CatalogGating;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * The single gate a seller's product must pass to leave draft and head toward
 * public visibility (US-SEL-03, BR-SEL-03):
 *   - at least one image is attached (US-SEL-03),
 *   - the price XOR rule holds (BR-SEL-03).
 * On success it moves the product to pending_review (when review is ON) or
 * straight to published. The plan product-count limit (BR-SEL-01) is a
 * create-time gate owned by {@see CreateProduct} — a status change does not add
 * a product, so it is not re-checked here. Approve/reject after review happen in
 * {@see DecideProductReview}.
 */
class PublishProduct
{
    public function publish(Product $product): Product
    {
        if (! $product->hasImage()) {
            throw ExceptionResponse::instance(__('catalog::messages.media_required_to_publish'), 422)
                ->setCustomBody(['media' => [__('catalog::messages.media_required_to_publish')]]);
        }

        $product->ensureValidPricing();

        $product->forceFill([
            'status' => CatalogGating::requiresReviewOnCreate()
                ? ProductStatus::PendingReview
                : ProductStatus::Published,
            'rejection_reason' => null,
        ])->save();

        return $product;
    }
}
