<?php

namespace Modules\Inquiries\Policies;

use App\Models\User;
use Modules\Auth\Enums\AccountType;
use Modules\Inquiries\Models\Inquiry;

/**
 * Authorization for inquiries, per the Spec Section 8 matrix row "Inquiries":
 * sellers get R/U on their own inbound inquiries, buyers get C/R on their
 * own sent inquiries — neither party may act on an inquiry they're not part
 * of, and only the seller drives lead_status (a buyer has no U ability).
 */
class InquiryPolicy
{
    /**
     * Create: Wholesaler and Retailer act as buyers in R1. Importer is
     * seller-only in R1 (no C ability per §8); Customer is R3-only
     * (US-INQ-10) and out of scope here.
     */
    public function create(User $user): bool
    {
        return in_array($user->account_type, [AccountType::Wholesaler, AccountType::Retailer], true);
    }

    /**
     * Buyer or seller-business owner may view; anyone else gets 403.
     */
    public function view(User $user, Inquiry $inquiry): bool
    {
        return $this->isBuyer($user, $inquiry) || $this->isSeller($user, $inquiry);
    }

    /**
     * lead_status transitions are seller-only (US-INQ-06/07, §8: buyer has
     * no U ability on Inquiries).
     */
    public function update(User $user, Inquiry $inquiry): bool
    {
        return $this->isSeller($user, $inquiry);
    }

    private function isBuyer(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->buyer_id === $user->getKey();
    }

    private function isSeller(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->seller_business_id === $user->businessAccount?->getKey();
    }
}
