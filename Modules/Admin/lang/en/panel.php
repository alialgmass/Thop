<?php

return [
    'audit_log' => [
        'label' => 'Audit log entry',
        'plural' => 'Audit log',
    ],

    'columns' => [
        'when' => 'When',
        'actor' => 'Actor',
        'action' => 'Action',
        'entity' => 'Entity',
        'entity_id' => 'Entity ID',
    ],

    'filters' => [
        'action' => 'Action',
        'actor' => 'Actor',
        'entity' => 'Entity',
    ],

    'dashboard' => [
        'active_sellers' => 'Active sellers',
        'active_products' => 'Active products',
        'active_buyers' => 'Active buyers',
        'inquiries' => 'Inquiries',
        'top_zero_result_term' => 'Top zero-result term',
        'zero_result_hint' => 'A search term that returned nothing — unmet demand.',
        'last_days' => 'Last :days days',
        'none' => 'None',
    ],

    'banner' => [
        'label' => 'Banner',
        'plural' => 'Banners',
        'fields' => [
            'image' => 'Image',
            'link_url' => 'Link URL',
            'position' => 'Position',
            'starts_at' => 'Starts at',
            'starts_at_hint' => 'Leave empty to start immediately.',
            'ends_at' => 'Ends at',
            'ends_at_hint' => 'Leave empty for no end date.',
            'immediately' => 'Immediately',
            'no_end' => 'No end date',
            'is_active' => 'Active',
        ],
        'actions' => [
            'remove' => 'Remove',
            'removed' => 'Banner removed.',
        ],
    ],

    'supplier_onboarding' => [
        'label' => 'Onboarded supplier',
        'plural' => 'Assisted onboarding',
        'onboard' => 'Onboard supplier',
        'steps' => [
            'account' => 'Account',
            'business' => 'Business profile',
        ],
        'fields' => [
            'phone' => 'Phone',
            'account_type' => 'Account type',
            'email' => 'Email address',
            'language' => 'Language',
            'password' => 'Password',
            'password_confirmation' => 'Confirm password',
            'company_name' => 'Company name',
            'activity' => 'Activity',
            'governorate' => 'Governorate',
            'address' => 'Address',
            'contact_person' => 'Contact person',
            'verification_status' => 'Verification status',
        ],
        'account_types' => [
            'importer' => 'Importer',
            'wholesaler' => 'Wholesaler',
            'retailer' => 'Retailer',
        ],
    ],
];
