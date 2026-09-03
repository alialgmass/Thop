<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an admin tries to approve/reject a product that is not awaiting
 * review, or a seller requests an illegal status transition (US-SEL-11,
 * BR-SEL-03). Rendered as a 409 envelope (custom code 4093), mirroring the
 * VerificationNotPendingException (4092) pattern.
 */
class ProductNotInReviewException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('catalog::messages.not_in_review');

        parent::__construct($message, 409);

        $this->setCustomCode(4093)
            ->setCustomBody(['status' => [$message]]);
    }
}
