<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Models\OtpRequest;
use PHPUnit\Framework\Attributes\Test;

class OtpRequestTest extends AuthModuleTestCase
{
    #[Test]
    public function it_sends_a_hashed_code_and_never_returns_it(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
        ]);

        $response->assertOk();

        $this->otp->assertSentTo('+201012345678');
        $code = $this->otp->lastCodeFor('+201012345678');

        $this->assertStringNotContainsString($code, $response->getContent());

        $row = OtpRequest::query()->firstOrFail();
        $this->assertSame('+201012345678', $row->phone);
        $this->assertNotSame($code, $row->code_hash);
        $this->assertTrue($row->expires_at->isFuture());
        $this->assertTrue($row->expires_at->lessThanOrEqualTo(now()->addSeconds(300)));
    }

    #[Test]
    public function requesting_a_new_code_supersedes_the_previous_one(): void
    {
        $payload = ['phone' => '01012345678', 'purpose' => OtpPurpose::Registration->value];

        $this->postJson('/api/v1/auth/otp/request', $payload)->assertOk();
        $this->postJson('/api/v1/auth/otp/request', $payload)->assertOk();

        $this->assertSame(1, OtpRequest::query()->whereNull('consumed_at')->count());
        $this->assertSame(2, OtpRequest::query()->count());
    }

    #[Test]
    public function it_throttles_repeated_requests_for_the_same_phone(): void
    {
        $payload = ['phone' => '01012345678', 'purpose' => OtpPurpose::Registration->value];

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/otp/request', $payload)->assertOk();
        }

        $this->postJson('/api/v1/auth/otp/request', $payload)->assertStatus(429);
    }

    #[Test]
    public function registration_request_for_an_existing_phone_routes_to_login_and_sends_nothing(): void
    {
        User::factory()->create(['phone' => '+201012345678']);

        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
        ])->assertStatus(409);

        $this->otp->assertNothingSent();
    }

    #[Test]
    public function password_reset_for_an_unknown_phone_is_indistinguishable_from_a_real_send(): void
    {
        $known = User::factory()->create(['phone' => '+201012345678']);

        $real = $this->postJson('/api/v1/auth/otp/request', [
            'phone' => $known->phone,
            'purpose' => OtpPurpose::PasswordReset->value,
        ])->assertOk();

        $unknown = $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '01298765432',
            'purpose' => OtpPurpose::PasswordReset->value,
        ])->assertOk();

        $this->assertSame($real->json(), $unknown->json());
        $this->otp->assertNothingSentTo('+201298765432');
    }

    #[Test]
    public function it_rejects_a_non_egyptian_phone_number(): void
    {
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '+1 555 0100',
            'purpose' => OtpPurpose::Registration->value,
        ])->assertStatus(400)->assertJsonStructure(['body' => ['phone']]);
    }

    #[Test]
    public function it_surfaces_a_localized_error_when_the_provider_fails(): void
    {
        $this->otp->fail();

        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '01012345678',
            'purpose' => OtpPurpose::Registration->value,
        ])->assertStatus(503)->assertJsonPath('message', __('auth::otp.delivery_failed'));
    }
}
