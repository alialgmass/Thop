<?php

namespace Modules\Inquiries\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;

/**
 * Thrown when an admin tries to resolve a report that's already resolved or
 * dismissed (Phase 9 · T9). Rendered as a 409 envelope, mirroring
 * ProductNotInReviewException's pattern.
 */
class ReportAlreadyResolvedException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('inquiries::messages.report_already_resolved');

        parent::__construct($message, 409);

        $this->setCustomCode(4097)
            ->setCustomBody(['status' => [$message]]);
    }
}
