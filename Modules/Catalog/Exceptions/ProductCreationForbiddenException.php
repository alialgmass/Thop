<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when a buyer tries to create a product (the Authorization Matrix makes
 * product creation a seller-only action), or when a seller's subscription has
 * lapsed into a restricted state and product creation must be blocked. Rendered
 * as a 403 by Core's policy handler for the former; this class carries the
 * restricted-state reason as a 422 business rule so the client can route the
 * user to renew. Mirrors the Businesses explicit-reason pattern.
 */
class ProductCreationForbiddenException extends ExceptionResponse
{
    public function __construct(string $message)
    {
        parent::__construct($message, 403);
    }
}
