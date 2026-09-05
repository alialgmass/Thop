<?php

namespace Modules\Inquiries\Http\Concerns;

use Modules\Businesses\Models\BusinessAccount;
use Modules\Inquiries\Enums\InquiryParty;
use Modules\Inquiries\Exceptions\InquiryLimitExceededException;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * The single check for "may this party create another inquiry/RFQ"
 * (BR-INQ-02) — shared by InquiryController and RfqController so the
 * seller/buyer gating logic can't drift between the two creation paths.
 */
trait EnforcesInquiryLimit
{
    /**
     * `$optional` lets the buyer side no-op when its plan doesn't define
     * inquiry_limit at all, rather than treating "undefined" as "over limit"
     * the way the seller side must (every seller plan defines this key).
     */
    private function assertWithinInquiryLimit(
        EntitlementService $entitlements,
        BusinessAccount $business,
        InquiryParty $party,
        bool $optional = false,
    ): void {
        if ($optional && $entitlements->get($business, 'inquiry_limit') === null) {
            return;
        }

        if (! $entitlements->can($business, 'inquiry_limit')) {
            throw new InquiryLimitExceededException($party);
        }
    }
}
