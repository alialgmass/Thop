<?php

/**
 * Shared Filament admin-panel copy: navigation groups and the Access-Control
 * resources (Users / Roles / Permissions) that live in app/Filament.
 * Module-owned resources keep their strings in their own module lang directory.
 */
return [
    'brand' => 'THOB Admin',

    'nav' => [
        'moderation' => 'Moderation',
        'billing' => 'Billing',
        'access_control' => 'Access Control',
        'system' => 'System',
    ],

    'common' => [
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'guard_name' => 'Guard',
    ],

    'user' => [
        'label' => 'User',
        'plural' => 'Users',
        'fields' => [
            'phone' => 'Phone',
            'email' => 'Email address',
            'password' => 'Password',
            'account_type' => 'Account type',
            'language' => 'Language',
            'status' => 'Status',
        ],
        'actions' => [
            'suspend' => 'Suspend',
            'suspend_confirm' => 'I understand this immediately revokes this account\'s access.',
            'suspended' => 'Account suspended.',
            'reactivate' => 'Reactivate',
            'reactivated' => 'Account reactivated.',
        ],
    ],

    'role' => [
        'label' => 'Role',
        'plural' => 'Roles',
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'permission' => [
        'label' => 'Permission',
        'plural' => 'Permissions',
        'fields' => [
            'name' => 'Name',
        ],
    ],
];
