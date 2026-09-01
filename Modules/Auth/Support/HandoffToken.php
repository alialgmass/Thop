<?php

namespace Modules\Auth\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Modules\Auth\Enums\OtpPurpose;

/**
 * A short-lived, tamper-proof token handed to the client after a successful OTP
 * verification and consumed by the follow-up step (register / password reset).
 * It carries the already-verified phone number so that step never re-checks a
 * code or trusts a client-supplied phone.
 */
class HandoffToken
{
    public static function issue(string $phone, OtpPurpose $purpose): string
    {
        return Crypt::encryptString(json_encode([
            'phone' => $phone,
            'purpose' => $purpose->value,
            'expires_at' => now()->addSeconds((int) config('auth.otp.handoff_ttl_seconds', 600))->timestamp,
        ]));
    }

    /**
     * Return the verified phone number carried by a valid token for the given
     * purpose, or null when the token is missing, tampered, expired, or issued
     * for a different purpose.
     */
    public static function verifiedPhone(?string $token, OtpPurpose $purpose): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload)
            || ($payload['purpose'] ?? null) !== $purpose->value
            || ($payload['expires_at'] ?? 0) < now()->timestamp
        ) {
            return null;
        }

        return $payload['phone'] ?? null;
    }
}
