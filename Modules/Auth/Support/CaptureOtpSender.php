<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Contracts\OtpSender;

/**
 * Local/testing-only OTP sender. Stashes the plaintext code in the cache under
 * `otp:{phone}` for the OTP's lifetime so a developer running the flow by hand
 * (or a Postman run) can read it back — the production `log` sender deliberately
 * never emits the code (SEC-NFR-02).
 *
 * The binding is guarded on the environment in `AuthServiceProvider` and can
 * never be selected in staging or production.
 */
class CaptureOtpSender implements OtpSender
{
    public static function cacheKey(string $phone): string
    {
        return 'otp:'.$phone;
    }

    public function send(string $phone, string $code): void
    {
        Cache::put(self::cacheKey($phone), $code, (int) config('auth.otp.ttl_seconds', 300));

        Log::info('OTP issued (captured for local dev)', ['phone' => $phone]);
    }
}
