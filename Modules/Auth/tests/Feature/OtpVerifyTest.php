<?php

namespace Modules\Auth\Tests\Feature;

use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Models\OtpRequest;
use PHPUnit\Framework\Attributes\Test;

class OtpVerifyTest extends AuthModuleTestCase
{
    private function requestCode(string $phone = '01012345678'): string
    {
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => $phone,
            'purpose' => OtpPurpose::Registration->value,
        ])->assertOk();

        return $this->otp->lastCodeFor('+201012345678');
    }

    #[Test]
    public function a_correct_code_returns_a_registration_token(): void
    {
        $code = $this->requestCode();

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['message', 'body' => ['registration_token']]);

        $this->assertNotNull(OtpRequest::query()->firstOrFail()->consumed_at);
    }

    #[Test]
    public function an_expired_code_is_rejected(): void
    {
        $code = $this->requestCode();
        OtpRequest::query()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
            'code' => $code,
        ])->assertStatus(422)->assertJsonPath('message', __('auth::otp.expired'));
    }

    #[Test]
    public function three_wrong_attempts_lock_the_code(): void
    {
        $code = $this->requestCode();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/otp/verify', [
                'phone' => '01012345678',
                'purpose' => OtpPurpose::Registration->value,
                'code' => '000000',
            ])->assertStatus(422);
        }

        // Even the correct code no longer works.
        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
            'code' => $code,
        ])->assertStatus(422)->assertJsonPath('message', __('auth::otp.locked'));
    }

    #[Test]
    public function verifying_without_an_active_request_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
            'code' => '123456',
        ])->assertStatus(422)->assertJsonPath('message', __('auth::otp.no_active_request'));
    }
}
