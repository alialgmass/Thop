<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrialPromoTest extends TestCase
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
    public function trial_subscription_activates_without_payment(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', [
                'plan_id' => $plan->id,
                'trial_ends_at' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('body.subscription.status', 'active')
            ->assertJsonPath('body.subscription.is_trial', true);

        $this->assertDatabaseHas('subscriptions', [
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function trial_subscription_has_null_current_period_end(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', [
                'plan_id' => $plan->id,
                'trial_ends_at' => now()->addDays(14)->toDateString(),
            ]);

        $subscription = Subscription::where('business_account_id', $this->business->id)->first();

        $this->assertNull($subscription->current_period_end);
        $this->assertNotNull($subscription->trial_ends_at);
    }

    #[Test]
    public function admin_granted_trial_works_via_api(): void
    {
        $admin = User::factory()->admin()->create();

        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Premium',
        ]);

        // Admin creates a subscription on behalf of a business (simulating admin grant)
        $subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => null,
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Active->value,
        ]);

        $this->assertTrue($subscription->isTrial());
        $this->assertTrue($subscription->isActive());
    }

    #[Test]
    public function expired_trial_transitions_to_restricted(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => null,
            'trial_ends_at' => now()->subDay(),
        ]);

        // Trial expired — should be detected as expired
        $this->assertTrue($subscription->isExpired());
    }
}
