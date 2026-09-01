<?php

return [

    /*
    |--------------------------------------------------------------------------
    | One-Time Password
    |--------------------------------------------------------------------------
    |
    | Settings for the phone-verification OTP flow. The 5-minute expiry is
    | fixed by SEC-NFR-02; the remaining values are Implementation Assumptions
    | (the source document does not specify exact numbers) and are tuned here.
    |
    */

    // Which OtpSender implementation to bind (see AuthServiceProvider::OTP_DRIVERS).
    'driver' => env('OTP_DRIVER', 'log'),

    'length' => 6,

    'ttl_seconds' => 300,

    'max_attempts' => 3,

    // Short-lived signed token returned after a successful OTP verification,
    // consumed by register / password-reset.
    'handoff_ttl_seconds' => 600,

    'throttle' => [
        // OTP request calls allowed per phone number per window.
        'request_per_minute' => 3,
        // OTP verify calls allowed per phone number per window.
        'verify_per_minute' => 5,
        // Login attempts allowed per phone+IP per window.
        'login_per_minute' => 5,
    ],
];
