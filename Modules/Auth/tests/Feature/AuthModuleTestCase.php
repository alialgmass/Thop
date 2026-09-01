<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Support\PhoneNumber;
use Modules\Auth\Tests\Support\FakeOtpSender;
use Tests\TestCase;

abstract class AuthModuleTestCase extends TestCase
{
    use RefreshDatabase;

    protected FakeOtpSender $otp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otp = new FakeOtpSender;
        $this->app->instance(OtpSender::class, $this->otp);
    }

    /**
     * Drive the OTP request + verify pair and return the handoff token
     * (registration_token / reset_token).
     */
    protected function completeOtp(string $phone, OtpPurpose $purpose): string
    {
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => $phone,
            'purpose' => $purpose->value,
        ])->assertOk();

        $tokenKey = $purpose === OtpPurpose::Registration ? 'registration_token' : 'reset_token';

        return $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'purpose' => $purpose->value,
            'code' => $this->otp->lastCodeFor(PhoneNumber::normalize($phone)),
        ])->assertOk()->json($tokenKey);
    }
}
