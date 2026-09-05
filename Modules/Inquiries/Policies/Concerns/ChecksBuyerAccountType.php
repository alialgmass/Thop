<?php

namespace Modules\Inquiries\Policies\Concerns;

use App\Models\User;
use Modules\Auth\Enums\AccountType;

/**
 * Which account types may act as a buyer in R1 (InquiryPolicy::create,
 * RfqPolicy::create) — Wholesaler and Retailer; Importer is seller-only in
 * R1 (no C ability per §8), Customer is R3-only (US-INQ-10). One place for
 * this list so both policies can't drift apart.
 */
trait ChecksBuyerAccountType
{
    private function isBuyerAccountType(User $user): bool
    {
        return in_array($user->account_type, [AccountType::Wholesaler, AccountType::Retailer], true);
    }
}
