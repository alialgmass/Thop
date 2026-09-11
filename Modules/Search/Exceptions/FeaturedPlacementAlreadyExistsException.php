<?php

namespace Modules\Search\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an admin tries to feature an item in a slot it already holds
 * (unique `(featurable_type, featurable_id, slot)`, Phase 9 · T5). Rendered
 * as a 409 envelope, mirroring ProductNotInReviewException's pattern.
 */
class FeaturedPlacementAlreadyExistsException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('search::messages.placement_already_exists');

        parent::__construct($message, 409);

        $this->setCustomCode(4094)
            ->setCustomBody(['slot' => [$message]]);
    }
}
