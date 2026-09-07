<?php

return [
    'plan' => [
        'label' => 'Subscription plan',
        'plural' => 'Subscription plans',
    ],
    'subscription' => [
        'label' => 'Subscription',
        'plural' => 'Subscriptions',
    ],

    'sections' => [
        'plan_details' => 'Plan details',
        'entitlements' => 'Entitlements',
        'entitlements_hint' => 'Key/value pairs — admin can edit without a migration (MNT-NFR-02).',
        'business' => 'Business',
        'subscription' => 'Subscription',
    ],

    'fields' => [
        'name' => 'Name',
        'account_type' => 'Account type',
        'price' => 'Price',
        'price_custom_hint' => 'Leave empty for custom pricing',
        'billing_cycle' => 'Billing cycle',
        'cycle' => 'Cycle',
        'is_active' => 'Active',
        'entitlements' => 'Entitlements',
        'entitlement_key' => 'Key',
        'entitlement_value' => 'Value',
        'entitlement_key_hint' => 'e.g. product_limit',
        'entitlement_value_hint' => 'e.g. Large, true, 100',
        'add_entitlement' => 'Add entitlement',
        'custom' => 'Custom',
        'none' => 'None',
        'company' => 'Company',
        'activity' => 'Activity',
        'owner_phone' => 'Owner phone',
        'owner_email' => 'Owner email',
        'plan' => 'Plan',
        'status' => 'Status',
        'period_ends' => 'Period ends',
        'trial_ends' => 'Trial ends',
        'subscribed_since' => 'Subscribed since',
        'new_period_end' => 'New period end date',
    ],

    'account_types' => [
        'importer' => 'Importer',
        'wholesaler' => 'Wholesaler',
        'retailer' => 'Retailer',
    ],

    'billing_cycles' => [
        'monthly' => 'Monthly',
        'annual' => 'Annual',
    ],

    'actions' => [
        'grant_trial' => 'Grant trial / promo',
        'extend_period' => 'Extend period',
        'cancel' => 'Cancel subscription',
        'trial_granted' => 'Trial / promo granted.',
        'period_extended' => 'Subscription period extended.',
        'cancelled' => 'Subscription cancelled.',
    ],
];
