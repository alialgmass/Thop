<?php

namespace Modules\Inquiries\Policies;

use App\Models\User;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Policies\Concerns\ChecksBuyerAccountType;

/**
 * Authorization for inquiries, per the Spec Section 8 matrix row "Inquiries":
 * sellers get R/U on their own inbound inquiries, buyers get C/R on their
 * own sent inquiries — neither party may act on an inquiry they're not part
 * of, and only the seller drives lead_status (a buyer has no U ability).
 */
class InquiryPolicy
{
    use ChecksBuyerAccountType;

    /**
     * Create: Wholesaler and Retailer act as buyers in R1. Importer is
     * seller-only in R1 (no C ability per §8); Customer is R3-only
     * (US-INQ-10) and out of scope here.
     */
    public function create(User $user): bool
    {
        return $this->isBuyerAccountType($user);
    }

    /**
     * Buyer or seller-business owner may view; anyone else gets 403.
     */
    public function view(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->involvesUser($user);
    }

    /**
     * lead_status transitions are seller-only (US-INQ-06/07, §8: buyer has
     * no U ability on Inquiries).
     */
    public function update(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->isSeller($user);
    }

    /**
     * Either party may report an inquiry (US-INQ-09).
     */
    public function report(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->involvesUser($user);
    }
}
