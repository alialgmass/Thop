<?php

namespace Modules\Notifications\Enums;

/**
 * The preference categories a user can tune (US-NOT-02). Each notification
 * class declares the one it belongs to; the resolver keys preference lookups
 * on it. `Marketing` is the odd one out — opt-in only, never on by default,
 * and never merged with the operational categories (US-NOT-04).
 */
enum NotificationCategory: string
{
    case Verification = 'verification';
    case ProductReview = 'product_review';
    case Inquiry = 'inquiry';
    case Rfq = 'rfq';
    case Quotation = 'quotation';
    case Message = 'message';
    case Subscription = 'subscription';
    case Marketing = 'marketing';

    /**
     * Whether this category is enabled when the user has expressed no
     * preference. Everything operational defaults on; marketing defaults off.
     */
    public function defaultEnabled(): bool
    {
        return $this !== self::Marketing;
    }

    /**
     * Account/financial categories — their mail/sms delivery is forced on
     * regardless of preference (US-NOT-03). One list, shared by the resolver
     * and the preferences endpoint.
     */
    public function isOperational(): bool
    {
        return in_array($this, [self::Verification, self::Subscription], true);
    }
}
