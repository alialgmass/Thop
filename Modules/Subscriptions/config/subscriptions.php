<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expiry reminder window
    |--------------------------------------------------------------------------
    |
    | How many days before `current_period_end` the `subscriptions:notify-expiring`
    | command fires SubscriptionExpiring (US-SUB-08 / US-NOT-01). The SRS
    | requires the reminder but not the lead time — Implementation Assumption.
    |
    */

    'expiring_reminder_days' => (int) env('SUBSCRIPTION_EXPIRING_REMINDER_DAYS', 7),
];
