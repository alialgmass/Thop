<?php

namespace Modules\Admin\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an admin tries to reactivate an account that isn't currently
 * suspended (Phase 9 · T8).
 */
class AccountNotSuspendedException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('admin::messages.account_not_suspended');

        parent::__construct($message, 409);

        $this->setCustomCode(4096)
            ->setCustomBody(['status' => [$message]]);
    }
}
