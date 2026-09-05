<?php

namespace Modules\Inquiries\Http\Concerns;

use Modules\Catalog\Models\Product;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * "Does this product belong to this seller business" — the one shape shared
 * by an inquiry's optional product/seller_business_id cross-check and an
 * RFQ's mandatory product/inquiry-seller check.
 */
trait GuardsProductOwnership
{
    private function assertProductBelongsToBusiness(Product $product, int $expectedBusinessId, string $messageKey, string $field): void
    {
        if ($product->business_account_id !== $expectedBusinessId) {
            $message = __($messageKey);

            throw ExceptionResponse::instance($message, 422)
                ->setCustomBody([$field => [$message]]);
        }
    }
}
