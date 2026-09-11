<?php

namespace Modules\Admin\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an admin tries to suspend another admin account (Phase 9 ·
 * T8) — a policy restriction, not a state conflict, so 403 rather than 409.
 */
class CannotModerateAdminAccountException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('admin::messages.cannot_moderate_admin_account');

        parent::__construct($message, 403);

        $this->setCustomCode(4032)
            ->setCustomBody(['status' => [$message]]);
    }
}
