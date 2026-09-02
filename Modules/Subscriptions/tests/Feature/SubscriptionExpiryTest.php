<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Subscriptions\Services\EntitlementService;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionExpiryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function expired_subscription_transitions_to_restricted_state(): void
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
            'current_period_end' => now()->subDay(),
        ]);

        // Simulate expiry processing
        $subscription->markExpired();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Restricted->value,
        ]);
    }

    #[Test]
    public function expired_subscription_is_detected_by_is_expired(): void
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
            'current_period_end' => now()->subDay(),
        ]);

        $this->assertTrue($subscription->isExpired());
    }

    #[Test]
    public function active_subscription_is_not_expired(): void
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

        $this->assertFalse($subscription->isExpired());
    }

    #[Test]
    public function entitlement_checks_fail_after_expiry(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'restricted',
            'current_period_end' => now()->subDay(),
        ]);

        $service = app(EntitlementService::class);

        // All entitlements should return false for expired subscription
        $this->assertFalse($service->can($business, 'product_limit'));
        $this->assertFalse($service->can($business, 'search_priority'));
        $this->assertNull($service->get($business, 'analytics_depth'));
    }

    #[Test]
    public function trial_subscription_is_detected(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);

        $subscription = Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => null,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($subscription->isTrial());
    }

    #[Test]
    public function scope_active_excludes_period_expired_subscription(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->subDay(),
        ]);

        $active = Subscription::activeForBusiness($business->id)->get();

        $this->assertTrue($active->isEmpty());
    }

    #[Test]
    public function scope_active_includes_current_period_subscription(): void
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

        $active = Subscription::activeForBusiness($business->id)->get();

        $this->assertTrue($active->contains('id', $subscription->id));
    }

    #[Test]
    public function scope_active_excludes_trial_expired_subscription(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);
        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => null,
            'trial_ends_at' => now()->subDay(),
        ]);

        $active = Subscription::activeForBusiness($business->id)->get();

        $this->assertTrue($active->isEmpty());
    }
}
