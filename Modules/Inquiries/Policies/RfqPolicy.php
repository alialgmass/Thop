<?php

namespace Modules\Inquiries\Policies;

use App\Models\User;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Rfq;
use Modules\Inquiries\Policies\Concerns\ChecksBuyerAccountType;

/**
 * Authorization for RFQs, per the Spec Section 8 matrix row "RFQs": buyers
 * get C/R on their own, sellers get R only (they reply via a Quotation, not
 * by editing the RFQ itself).
 */
class RfqPolicy
{
    use ChecksBuyerAccountType;

    /**
     * Create: only the inquiry's own buyer, and only Wholesaler/Retailer
     * account types can be buyers in R1 (same gate as InquiryPolicy::create).
     */
    public function create(User $user, Inquiry $inquiry): bool
    {
        return $this->isBuyerAccountType($user) && $inquiry->isBuyer($user);
    }

    /**
     * Buyer or the inquiry's addressed seller may view; anyone else 403.
     */
    public function view(User $user, Rfq $rfq): bool
    {
        return $rfq->involvesUser($user);
    }
}
