<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Enums\AccountType;
use Modules\Auth\Enums\UserStatus;
use PHPUnit\Framework\Attributes\Test;

class AccountTypeTest extends AuthModuleTestCase
{
    #[Test]
    public function a_pending_user_can_choose_an_account_type_and_becomes_active(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/account-type', [
            'account_type' => AccountType::Importer->value,
        ])->assertOk()
            ->assertJsonPath('user.account_type', 'importer')
            ->assertJsonPath('user.status', 'active')
            ->assertJsonPath('user.next_onboarding_step', 'business_profile');

        $user->refresh();
        $this->assertSame(AccountType::Importer, $user->account_type);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    #[Test]
    public function customer_selection_reports_no_further_onboarding(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/account-type', [
            'account_type' => AccountType::Customer->value,
        ])->assertOk()->assertJsonPath('user.next_onboarding_step', 'none');
    }

    #[Test]
    public function the_account_type_cannot_be_changed_once_set(): void
    {
        $user = User::factory()->importer()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/account-type', [
            'account_type' => AccountType::Retailer->value,
        ])->assertStatus(409);

        $this->assertSame(AccountType::Importer, $user->refresh()->account_type);
    }

    #[Test]
    public function an_invalid_account_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/account-type', [
            'account_type' => 'distributor',
        ])->assertStatus(422)->assertJsonValidationErrorFor('account_type');
    }

    #[Test]
    public function the_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/account-type', [
            'account_type' => AccountType::Importer->value,
        ])->assertUnauthorized();
    }
}
