<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpgradeSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessAccount $business;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()
            ->for($this->user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $this->subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    #[Test]
    public function upgrade_creates_new_subscription_immediately(): void
    {
        $proPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'upgrade',
                'plan_id' => $proPlan->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('body.subscription.status', 'active');

        // Old subscription should be cancelled
        $this->assertDatabaseHas('subscriptions', [
            'id' => $this->subscription->id,
            'status' => 'cancelled',
        ]);

        // New active subscription with Pro plan should exist
        $this->assertDatabaseHas('subscriptions', [
            'business_account_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function upgrade_is_effective_immediately(): void
    {
        $proPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'upgrade',
                'plan_id' => $proPlan->id,
            ]);

        // The new subscription should be active right now
        $newSubscription = Subscription::where('business_account_id', $this->business->id)
            ->where('plan_id', $proPlan->id)
            ->first();

        $this->assertNotNull($newSubscription);
        $this->assertTrue($newSubscription->isActive());
    }

    #[Test]
    public function cannot_upgrade_non_active_subscription(): void
    {
        $this->subscription->update(['status' => 'cancelled']);

        $proPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'upgrade',
                'plan_id' => $proPlan->id,
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function cannot_upgrade_another_users_subscription(): void
    {
        $intruder = User::factory()->importer()->create();

        $proPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $response = $this->actingAs($intruder)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'upgrade',
                'plan_id' => $proPlan->id,
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function invalid_action_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'invalid_action',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('action');
    }
}
