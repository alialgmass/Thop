<?php

namespace Modules\Auth\Enums;

enum AccountType: string
{
    case Importer = 'importer';
    case Wholesaler = 'wholesaler';
    case Retailer = 'retailer';
    case Customer = 'customer';

    /**
     * The onboarding step reported for a user that has not chosen a type yet.
     */
    public const SelectionStep = 'account_type_selection';

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

    /**
     * Localized short label for the chooser screen (US-ACC-02).
     */
    public function label(): string
    {
        return __("auth::account_types.{$this->value}.label");
    }

    /**
     * Localized one-line description for the chooser screen (US-ACC-02).
     */
    public function description(): string
    {
        return __("auth::account_types.{$this->value}.description");
    }

    /**
     * The full selectable list with bilingual presentation for the current locale.
     *
     * @return list<array{value: string, label: string, description: string, requires_business_profile: bool}>
     */
    public static function catalog(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
            'requires_business_profile' => $type->requiresBusinessProfile(),
        ], self::cases());
    }
}
