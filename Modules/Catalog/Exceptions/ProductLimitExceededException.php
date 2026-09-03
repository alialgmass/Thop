<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when a product cannot be added because the business's active
 * subscription has reached its product_limit (BR-SEL-01, US-SEL-10). Rendered
 * as a 422 business-rule error carrying an upgrade prompt (custom code 4223,
 * near the Phase 2 4222 family). The limit is always resolved server-side from
 * the active subscription — never from any client claim (SEC-NFR-04).
 */
class ProductLimitExceededException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('catalog::messages.product_limit_exceeded');

        parent::__construct($message, 422);

        $this->setCustomCode(4223)
            ->setCustomBody(['product_limit' => [$message]]);
    }
}
