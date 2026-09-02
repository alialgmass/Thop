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
            ->assertJsonPath('body.user.account_type', 'importer')
            ->assertJsonPath('body.user.status', 'active')
            ->assertJsonPath('body.next_onboarding_step', 'business_profile');

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
        ])->assertOk()->assertJsonPath('body.next_onboarding_step', 'none');
    }

    #[Test]
    public function the_selectable_account_types_are_listed_with_bilingual_text(): void
    {
        $this->getJson('/api/v1/auth/account-types')
            ->assertOk()
            ->assertJsonCount(4, 'body.account_types')
            ->assertJsonStructure(['body' => ['account_types' => [['value', 'label', 'description', 'requires_business_profile']]]])
            ->assertJsonPath('body.account_types.0.value', 'importer');
    }

    #[Test]
    public function account_type_labels_switch_with_the_locale(): void
    {
        $this->assertSame(
            'Customer',
            $this->getJson('/api/v1/auth/account-types')->json('body.account_types.3.label'),
        );

        $this->app->setLocale('ar');

        $this->assertSame(
            'عميل',
            $this->getJson('/api/v1/auth/account-types')->json('body.account_types.3.label'),
        );
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
        ])->assertStatus(400)->assertJsonPath('message', 'The selected account type is invalid.')
            ->assertJsonStructure(['body' => ['account_type']]);
    }

    #[Test]
    public function the_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/account-type', [
            'account_type' => AccountType::Importer->value,
        ])->assertUnauthorized();
    }
}
