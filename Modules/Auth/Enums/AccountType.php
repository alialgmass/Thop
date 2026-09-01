<?php

namespace Modules\Auth\Enums;

enum AccountType: string
{
    case Importer = 'importer';
    case Wholesaler = 'wholesaler';
    case Retailer = 'retailer';
    case Customer = 'customer';

    /**
     * Whether this account type must complete a business profile after selection.
     */
    public function requiresBusinessProfile(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * The onboarding step the client should route to after selecting this type.
     */
    public function nextOnboardingStep(): string
    {
        return $this->requiresBusinessProfile() ? 'business_profile' : 'none';
    }
}
