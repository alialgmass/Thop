<?php

use Modules\Notifications\Channels\LogPushSender;
use Modules\Notifications\Channels\LogSmsSender;
use Modules\Notifications\Channels\NullPushSender;
use Modules\Notifications\Channels\NullSmsSender;

return [

    /*
    |--------------------------------------------------------------------------
    | Push delivery
    |--------------------------------------------------------------------------
    |
    | The SRS names no push vendor (SI-FR-04). `log` writes payloads to the log;
    | `null` discards them. A real FCM/APNs/OneSignal sender is a new class bound
    | to Modules\Notifications\Contracts\PushSender — no notification changes.
    |
    */

    'push' => [
        'driver' => env('NOTIFICATIONS_PUSH_DRIVER', 'log'),
        'log_channel' => env('NOTIFICATIONS_PUSH_LOG_CHANNEL', 'stack'),
        'drivers' => [
            'log' => LogPushSender::class,
            'null' => NullPushSender::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS delivery
    |--------------------------------------------------------------------------
    |
    | Used for account/financial notifications that must not be missed
    | (US-NOT-03). Same log/null seam as push.
    |
    */

    'sms' => [
        'driver' => env('NOTIFICATIONS_SMS_DRIVER', 'log'),
        'log_channel' => env('NOTIFICATIONS_SMS_LOG_CHANNEL', 'stack'),
        'drivers' => [
            'log' => LogSmsSender::class,
            'null' => NullSmsSender::class,
        ],
    ],
];
