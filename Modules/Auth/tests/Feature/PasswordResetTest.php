<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Enums\OtpPurpose;
use PHPUnit\Framework\Attributes\Test;

class PasswordResetTest extends AuthModuleTestCase
{
    #[Test]
    public function a_user_can_reset_their_password_via_otp(): void
    {
        User::factory()->create(['phone' => '+201012345678', 'password' => 'Old-Pass-1!']);

        $token = $this->completeOtp('01012345678', OtpPurpose::PasswordReset);

        $this->postJson('/api/v1/auth/password/reset', [
            'reset_token' => $token,
            'password' => 'New-Pass-2!',
            'password_confirmation' => 'New-Pass-2!',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678',
            'password' => 'Old-Pass-1!',
        ])->assertStatus(422);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678',
            'password' => 'New-Pass-2!',
        ])->assertOk();
    }

    #[Test]
    public function reset_request_for_an_unknown_phone_does_not_disclose_absence(): void
    {
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::PasswordReset->value,
        ])->assertOk();

        $this->otp->assertNothingSent();
    }

    #[Test]
    public function a_registration_token_cannot_be_used_to_reset_a_password(): void
    {
        User::factory()->create(['phone' => '+201012345678']);
        $registrationToken = $this->completeOtp('01198765432', OtpPurpose::Registration);

        $this->postJson('/api/v1/auth/password/reset', [
            'reset_token' => $registrationToken,
            'password' => 'New-Pass-2!',
            'password_confirmation' => 'New-Pass-2!',
        ])->assertStatus(422);
    }
}
