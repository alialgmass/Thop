<?php

namespace Modules\Inquiries\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Inquiries\Enums\InquiryParty;

/**
 * Thrown when an inquiry cannot be created because a party's active
 * subscription has reached its inquiry_limit (BR-INQ-02, US-INQ-08). The
 * buyer is never silently dropped — this renders as a clear 422 naming which
 * side hit the limit. The limit is always resolved server-side via
 * EntitlementService — never from any client claim (SEC-NFR-04).
 */
class InquiryLimitExceededException extends ExceptionResponse
{
    public function __construct(InquiryParty $party)
    {
        $message = $party === InquiryParty::Seller
            ? __('inquiries::messages.seller_limit_reached')
            : __('inquiries::messages.buyer_limit_reached');

        parent::__construct($message, 422);

        $this->setCustomCode(4231)
            ->setCustomBody(['inquiry_limit' => [$message]]);
    }
}
