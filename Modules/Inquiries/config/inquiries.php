<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inquiries
    |--------------------------------------------------------------------------
    |
    | The SRS requires rate-limiting inquiry creation (INQ-FR-09) but doesn't
    | specify a threshold — this number is an Implementation Assumption, tuned
    | here the same way Modules/Auth/config/otp.php documents its own.
    |
    */

    'throttle' => [
        // Inquiry (and, later, RFQ) creation calls allowed per user per minute.
        'create_per_minute' => 10,
    ],
];
