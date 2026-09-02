<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Subscriptions\Models\SubscriptionUsageCounter;
use Modules\Subscriptions\Services\EntitlementService;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EntitlementService::class);
    }

    #[Test]
    public function can_returns_false_when_no_subscription(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $this->assertFalse($this->service->can($business, 'product_limit'));
    }

    #[Test]
    public function can_returns_true_for_boolean_entitlement_when_enabled(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'search_priority', 'value' => 'true']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($this->service->can($business, 'search_priority'));
    }

    #[Test]
    public function can_returns_false_for_boolean_entitlement_when_disabled(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'search_priority', 'value' => 'false']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertFalse($this->service->can($business, 'search_priority'));
    }

    #[Test]
    public function can_checks_numeric_limit_against_usage(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '2']);

        $subscription = Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        // No usage yet — should be allowed
        $this->assertTrue($this->service->can($business, 'product_limit'));

        // Add one product — still under limit
        SubscriptionUsageCounter::create([
            'subscription_id' => $subscription->id,
            'key' => 'product_count',
            'current_value' => 1,
        ]);

        $this->assertTrue($this->service->can($business, 'product_limit'));

        // At limit — should be blocked
        SubscriptionUsageCounter::where('subscription_id', $subscription->id)
            ->where('key', 'product_count')
            ->update(['current_value' => 2]);

        $this->assertFalse($this->service->can($business, 'product_limit'));
    }

    #[Test]
    public function can_returns_false_for_expired_subscription(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'search_priority', 'value' => 'true']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'restricted',
            'current_period_end' => now()->subDay(),
        ]);

        $this->assertFalse($this->service->can($business, 'search_priority'));
    }

    #[Test]
    public function increment_and_decrement_usage_work_correctly(): void
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

        $this->service->incrementUsage($business, 'product_count', 3);

        $this->assertEquals(3, $this->service->currentUsage($business, 'product_count'));

        $this->service->decrementUsage($business, 'product_count', 1);

        $this->assertEquals(2, $this->service->currentUsage($business, 'product_count'));
    }

    #[Test]
    public function get_returns_raw_entitlement_value(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'analytics_depth', 'value' => 'advanced']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertEquals('advanced', $this->service->get($business, 'analytics_depth'));
    }

    #[Test]
    public function non_numeric_string_entitlement_is_treated_as_existing_capability(): void
    {
        $user = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Premium']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'support_level', 'value' => 'dedicated']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($this->service->can($business, 'support_level'));
    }
}
