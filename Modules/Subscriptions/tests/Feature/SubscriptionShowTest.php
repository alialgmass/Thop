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

class SubscriptionShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_view_their_subscription(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        $subscription = Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertOk()
            ->assertJsonPath('body.subscription.id', $subscription->id)
            ->assertJsonPath('body.subscription.plan.name', 'Basic')
            ->assertJsonPath('body.subscription.status', 'active');
    }

    #[Test]
    public function other_user_cannot_view_someone_elses_subscription(): void
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($owner, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        $subscription = Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $other = User::factory()->importer()->create();
        BusinessAccount::factory()
            ->for($other, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $response = $this->actingAs($other)
            ->getJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_view_any_subscription(): void
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($owner, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        $subscription = Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/subscriptions/{$subscription->id}");

        $response->assertOk();
    }

    #[Test]
    public function unauthenticated_user_cannot_view_subscription(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        $subscription = Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->getJson("/api/v1/subscriptions/{$subscription->id}")
            ->assertUnauthorized();
    }
}
