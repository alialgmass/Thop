<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Subscriptions\Services\EntitlementService;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientTamperingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessAccount $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()
            ->for($this->user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);
    }

    #[Test]
    public function forged_plan_id_in_request_body_is_ignored(): void
    {
        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $premiumPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Premium',
            'price' => null,
            'is_active' => false,
        ]);

        // Try to subscribe to an inactive plan via forged request
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', ['plan_id' => $premiumPlan->id]);

        // Should be rejected because plan is inactive (validation checks is_active)
        $response->assertStatus(422)
            ->assertJsonValidationErrors('plan_id');
    }

    #[Test]
    public function entitlement_always_checked_server_side_not_from_client(): void
    {
        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $service = app(EntitlementService::class);

        // Server-side check: Basic plan doesn't have search_priority
        // The service resolves this from DB, not from any client input
        $this->assertFalse($service->can($this->business, 'search_priority'));
    }

    #[Test]
    public function upgrade_requires_valid_plan_id_from_db(): void
    {
        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        // Try to upgrade to a non-existent plan
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}", [
                'action' => 'upgrade',
                'plan_id' => 9999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('plan_id');
    }

    #[Test]
    public function downgrade_requires_valid_plan_id_from_db(): void
    {
        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        // Try to downgrade to a non-existent plan
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}", [
                'action' => 'downgrade',
                'plan_id' => 9999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('plan_id');
    }

    #[Test]
    public function non_owner_cannot_modify_subscription(): void
    {
        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $intruder = User::factory()->importer()->create();

        $response = $this->actingAs($intruder)
            ->patchJson("/api/v1/subscriptions/{$subscription->id}", [
                'action' => 'cancel',
            ]);

        $response->assertForbidden();
    }
}
