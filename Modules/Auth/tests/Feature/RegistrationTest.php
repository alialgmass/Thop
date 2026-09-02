<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Enums\UserStatus;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends AuthModuleTestCase
{
    #[Test]
    public function a_verified_phone_can_register_and_receives_a_working_token(): void
    {
        $token = $this->completeOtp('01012345678', OtpPurpose::Registration);

        $response = $this->postJson('/api/v1/auth/register', [
            'registration_token' => $token,
            'password' => 'Str0ng-Pass!',
            'password_confirmation' => 'Str0ng-Pass!',
        ])->assertCreated()->assertJsonStructure(['body' => ['token', 'user' => ['id', 'phone', 'account_type', 'status']]]);

        $user = User::query()->firstOrFail();
        $this->assertSame('+201012345678', $user->phone);
        $this->assertNull($user->account_type);
        $this->assertSame(UserStatus::PendingTypeSelection, $user->status);

        $this->withToken($response->json('body.token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('body.user.phone', '+201012345678');
    }

    #[Test]
    public function registration_without_a_valid_token_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'registration_token' => 'not-a-real-token',
            'password' => 'Str0ng-Pass!',
            'password_confirmation' => 'Str0ng-Pass!',
        ])->assertStatus(422)->assertJsonStructure(['body' => ['registration_token']]);
    }

    #[Test]
    public function email_is_optional_but_must_be_unique_when_given(): void
    {
        $token = $this->completeOtp('01012345678', OtpPurpose::Registration);

        $this->postJson('/api/v1/auth/register', [
            'registration_token' => $token,
            'password' => 'Str0ng-Pass!',
            'password_confirmation' => 'Str0ng-Pass!',
        ])->assertCreated();

        $this->assertNull(User::query()->firstOrFail()->email);

        User::factory()->create(['email' => 'taken@example.com']);
        $token2 = $this->completeOtp('01198765432', OtpPurpose::Registration);

        $this->postJson('/api/v1/auth/register', [
            'registration_token' => $token2,
            'email' => 'taken@example.com',
            'password' => 'Str0ng-Pass!',
            'password_confirmation' => 'Str0ng-Pass!',
        ])->assertStatus(400)->assertJsonStructure(['body' => ['email']]);
    }

    #[Test]
    public function phone_format_variants_resolve_to_one_account(): void
    {
        $token = $this->completeOtp('01012345678', OtpPurpose::Registration);
        $this->postJson('/api/v1/auth/register', [
            'registration_token' => $token,
            'password' => 'Str0ng-Pass!',
            'password_confirmation' => 'Str0ng-Pass!',
        ])->assertCreated();

        // Same number, different notation -> already registered.
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '+201012345678',
            'purpose' => OtpPurpose::Registration->value,
        ])->assertStatus(409);

        $this->assertSame(1, User::query()->count());
    }
}
