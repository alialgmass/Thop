<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends AuthModuleTestCase
{
    #[Test]
    public function a_user_can_log_in_with_phone_and_password(): void
    {
        User::factory()->create([
            'phone' => '+201012345678',
            'password' => 'Str0ng-Pass!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678',
            'password' => 'Str0ng-Pass!',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'phone']]);
    }

    #[Test]
    public function a_wrong_password_is_rejected(): void
    {
        User::factory()->create(['phone' => '+201012345678', 'password' => 'Str0ng-Pass!']);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678',
            'password' => 'wrong',
        ])->assertStatus(422)->assertJsonPath('message', __('auth::otp.login_failed'));
    }

    #[Test]
    public function login_is_throttled_after_repeated_failures(): void
    {
        User::factory()->create(['phone' => '+201012345678', 'password' => 'Str0ng-Pass!']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone' => '01012345678',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678',
            'password' => 'Str0ng-Pass!',
        ])->assertStatus(429);
    }

    #[Test]
    public function logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['phone' => '+201012345678', 'password' => 'Str0ng-Pass!']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        // Drop the guard's cached user so the next call re-authenticates the token.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
