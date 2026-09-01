<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Facades\Log;
use Modules\Auth\Contracts\OtpSender;

/**
 * Default local/dev OTP sender. Records only that a code was issued for a
 * phone number — never the code itself — so the OTP channel stays confidential
 * even in logs (SEC-NFR-02).
 */
class LogOtpSender implements OtpSender
{
    public function send(string $phone, string $code): void
    {
        Log::info('OTP issued', ['phone' => $phone]);
    }
}
