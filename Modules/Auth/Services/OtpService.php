<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Exceptions\InvalidOtpException;
use Modules\Auth\Exceptions\OtpDeliveryException;
use Modules\Auth\Models\OtpRequest;

/**
 * Issues and verifies phone-verification codes. Kept as a service because the
 * same logic backs the OTP endpoints, registration, and password reset.
 */
class OtpService
{
    public function __construct(private readonly OtpSender $sender) {}

    /**
     * Generate a fresh code for the phone/purpose, invalidate any earlier
     * pending code, persist the hash, and hand the plaintext to the sender.
     *
     * @throws OtpDeliveryException
     */
    public function issue(string $phone, OtpPurpose $purpose): void
    {
        OtpRequest::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        OtpRequest::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose->value,
            'expires_at' => now()->addSeconds($this->ttlSeconds()),
            'attempts' => 0,
        ]);

        $this->sender->send($phone, $code);
    }

    /**
     * Verify a submitted code. Marks the request consumed on success.
     *
     * @throws InvalidOtpException
     */
    public function verify(string $phone, string $code, OtpPurpose $purpose): void
    {
        /** @var OtpRequest|null $request */
        $request = OtpRequest::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($request === null) {
            throw InvalidOtpException::noActiveRequest();
        }

        if ($request->isExpired()) {
            throw InvalidOtpException::expired();
        }

        if (! $request->hasAttemptsLeft($this->maxAttempts())) {
            throw InvalidOtpException::locked();
        }

        $request->increment('attempts');

        if (! Hash::check($code, $request->code_hash)) {
            if (! $request->fresh()->hasAttemptsLeft($this->maxAttempts())) {
                throw InvalidOtpException::locked();
            }

            throw InvalidOtpException::mismatch();
        }

        $request->forceFill(['consumed_at' => now()])->save();
    }

    private function generateCode(): string
    {
        $length = $this->codeLength();

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    private function codeLength(): int
    {
        return (int) config('auth.otp.length', 6);
    }

    private function ttlSeconds(): int
    {
        return (int) config('auth.otp.ttl_seconds', 300);
    }

    private function maxAttempts(): int
    {
        return (int) config('auth.otp.max_attempts', 3);
    }
}
