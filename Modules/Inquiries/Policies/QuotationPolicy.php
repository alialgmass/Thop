<?php

namespace Modules\Inquiries\Policies;

use App\Models\User;
use Modules\Inquiries\Models\Quotation;
use Modules\Inquiries\Models\Rfq;

/**
 * Authorization for quotations, per the Spec Section 8 matrix row
 * "Quotations": only the RFQ's addressed seller may create one (US-INQ-03 —
 * "Given I'm not the RFQ's target seller, When I try to quote, Then 403").
 */
class QuotationPolicy
{
    public function create(User $user, Rfq $rfq): bool
    {
        return $rfq->isSeller($user);
    }

    /**
     * Buyer or the addressed seller may view.
     */
    public function view(User $user, Quotation $quotation): bool
    {
        return $quotation->involvesUser($user);
    }
}
