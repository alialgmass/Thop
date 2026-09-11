<?php

namespace Modules\Admin\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an admin tries to suspend an already-suspended account
 * (Phase 9 · T8). Rendered as a 409 envelope, mirroring
 * ProductNotInReviewException's pattern.
 */
class AccountAlreadySuspendedException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('admin::messages.account_already_suspended');

        parent::__construct($message, 409);

        $this->setCustomCode(4095)
            ->setCustomBody(['status' => [$message]]);
    }
}
