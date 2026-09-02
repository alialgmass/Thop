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

class SubscribeTest extends TestCase
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
    public function user_can_subscribe_to_a_plan(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', ['plan_id' => $plan->id]);

        $response->assertCreated()
            ->assertJsonPath('body.subscription.plan.name', 'Basic')
            ->assertJsonPath('body.subscription.status', 'active');

        $this->assertDatabaseHas('subscriptions', [
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function subscribing_creates_trial_when_requested(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $trialEnd = now()->addDays(14)->toDateString();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', [
                'plan_id' => $plan->id,
                'trial_ends_at' => $trialEnd,
            ]);

        $response->assertCreated()
            ->assertJsonPath('body.subscription.is_trial', true);

        $this->assertDatabaseHas('subscriptions', [
            'business_account_id' => $this->business->id,
            'status' => 'active',
        ]);

        $this->assertNotNull(
            Subscription::where('business_account_id', $this->business->id)
                ->whereNotNull('trial_ends_at')
                ->first()
        );
    }

    #[Test]
    public function cannot_subscribe_with_duplicate_active_subscription(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', ['plan_id' => $plan->id]);

        $response->assertStatus(409);
    }

    #[Test]
    public function invalid_plan_id_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', ['plan_id' => 9999]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('plan_id');
    }

    #[Test]
    public function user_without_business_profile_cannot_subscribe(): void
    {
        $noBusinessUser = User::factory()->importer()->create();

        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $response = $this->actingAs($noBusinessUser)
            ->postJson('/api/v1/subscriptions', ['plan_id' => $plan->id]);

        $response->assertForbidden();
    }

    #[Test]
    public function unauthenticated_user_cannot_subscribe(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $response = $this->postJson('/api/v1/subscriptions', ['plan_id' => $plan->id]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function cannot_subscribe_to_plan_of_different_account_type(): void
    {
        $wholesalerPlan = SubscriptionPlan::create([
            'account_type' => 'wholesaler',
            'name' => 'Wholesale',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/subscriptions', ['plan_id' => $wholesalerPlan->id]);

        $response->assertStatus(422)
            ->assertJsonPath('custom_code', 4222);

        $this->assertDatabaseCount('subscriptions', 0);
    }
}
