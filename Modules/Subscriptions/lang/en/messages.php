<?php

return [
    'statuses' => [
        'active' => 'Active',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        'restricted' => 'Restricted',
    ],
    'billing_cycle' => [
        'monthly' => 'Monthly',
        'annual' => 'Annual',
    ],
    'messages' => [
        'plans_retrieved' => 'Subscription plans retrieved successfully',
        'already_subscribed' => 'You already have an active subscription',
        'subscribed' => 'Subscribed successfully',
        'unauthorized' => 'You are not authorized to access this subscription',
        'subscription_not_active' => 'Subscription is not active',
        'subscription_updated' => 'Subscription updated successfully',
        'usage_retrieved' => 'Usage data retrieved successfully',
        'subscription_retrieved' => 'Subscription retrieved successfully',
        'plan_type_mismatch' => 'This plan is not available for your account type',
    ],
    'validation' => [
        'plan_required' => 'A subscription plan is required',
        'plan_not_found' => 'The selected plan does not exist or is not active',
        'action_required' => 'An action is required (upgrade / downgrade / cancel)',
        'action_invalid' => 'The selected action is invalid',
        'plan_required_for_action' => 'A plan is required for this action',
    ],
];
