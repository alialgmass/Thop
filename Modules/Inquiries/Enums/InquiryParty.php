<?php

namespace Modules\Inquiries\Enums;

/**
 * Which side of an inquiry is being referred to — the buyer who sent it, or
 * the seller business it was sent to. Used wherever "buyer or seller" would
 * otherwise be a bare string (the `?role=` query param, entitlement-limit
 * errors), matching how the module already types other two/four-value
 * concepts ({@see LeadStatus}) instead of leaving them as primitives.
 */
enum InquiryParty: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';
}
