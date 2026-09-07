<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chat
    |--------------------------------------------------------------------------
    |
    | The SRS requires rate-limiting message sends (INQ-FR-09 / US-CHT-08) and
    | a message length limit (US-CHT-03) but specifies neither number — these
    | are Implementation Assumptions, tuned here the way Modules/Auth/config/otp.php
    | documents its own thresholds.
    |
    */

    'throttle' => [
        // Message sends allowed per user per minute before a 429.
        'send_per_minute' => 30,
    ],

    // Maximum length of a single message body.
    'message_max_length' => 4000,

    // Default page size for message history.
    'history_per_page' => 30,
];
