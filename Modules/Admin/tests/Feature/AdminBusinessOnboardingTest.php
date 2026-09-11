<?php

namespace Modules\Admin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin assisted supplier onboarding (US-ADM-10, Phase 9 · T10, issue #39).
 */
class AdminBusinessOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $governorate = Governorate::factory()->create();

        return array_merge([
            'phone' => '01012345678',
            'account_type' => 'importer',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Nile Textiles',
            'activity' => 'Fabric import',
            'governorate_id' => $governorate->id,
            'address' => '123 Industrial Zone',
            'contact_person' => 'Mona Ali',
        ], $overrides);
    }

    #[Test]
    public function a_non_admin_cannot_onboard_a_supplier(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->postJson('/api/v1/admin/businesses', $this->payload())
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_onboards_a_supplier_and_it_is_audited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/businesses', $this->payload())
            ->assertCreated();

        $user = User::query()->where('phone', '+201012345678')->firstOrFail();
        $this->assertSame('importer', $user->account_type->value);
        $this->assertSame('active', $user->status->value);

        $business = BusinessAccount::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($business->onboarded_by_admin);
        $this->assertSame('Nile Textiles', $business->company_name);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'supplier.onboarded', 'auditable_id' => $business->id,
        ]);
    }

    #[Test]
    public function the_onboarded_account_can_authenticate_and_use_seller_endpoints_like_a_self_registered_one(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/businesses', $this->payload())
            ->assertCreated();

        // actingAs() above cached the admin on the auth guard for the rest of
        // this test; forgetGuards() clears that so the token below actually
        // gets re-resolved instead of the request still running as the admin
        // (same fix as AdminAccountModerationTest's token-rejection check).
        $this->app['auth']->forgetGuards();

        $login = $this->postJson('/api/v1/auth/login', [
            'phone' => '01012345678', 'password' => 'Password123!',
        ])->assertOk();

        $token = $login->json('body.token');
        $this->assertNotNull($token);

        // A seller endpoint that needs only auth + ownership, not a
        // subscription — an onboarded account has no plan by default,
        // exactly like a self-registered one, so product creation itself
        // would 422 on the product_limit entitlement regardless of how the
        // account was created; that's not what this test is proving.
        $this->withToken($token)
            ->getJson('/api/v1/products/mine')
            ->assertOk();
    }

    #[Test]
    public function a_duplicate_phone_is_rejected_with_the_same_rule_as_public_registration(): void
    {
        User::factory()->create(['phone' => '+201012345678']);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/businesses', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('custom_code', 4091);

        $this->assertDatabaseCount('business_accounts', 0);
    }

    #[Test]
    public function missing_required_fields_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/businesses', [])
            ->assertStatus(400);

        $this->assertDatabaseCount('users', 1); // only the admin
    }
}
