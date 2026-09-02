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

class PeriodEndCommandTest extends TestCase
{
    use RefreshDatabase;

    private BusinessAccount $business;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()
            ->for($user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);
    }

    private function newPlan(string $name): SubscriptionPlan
    {
        return SubscriptionPlan::create(['account_type' => 'importer', 'name' => $name]);
    }

    private function subscription(array $attributes = []): Subscription
    {
        return Subscription::create(array_merge([
            'business_account_id' => $this->business->id,
            'plan_id' => $this->newPlan('Basic')->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ], $attributes));
    }

    #[Test]
    public function command_applies_pending_downgrade_at_period_end(): void
    {
        $basic = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        $pro = $this->newPlan('Pro');
        $subscription = $this->subscription([
            'plan_id' => $basic->id,
            'current_period_end' => now()->subDay(),
            'notes' => json_encode(['pending_plan_id' => $pro->id]),
        ]);

        $this->artisan('subscriptions:process-period-ends')->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals($pro->id, $subscription->plan_id);
        $this->assertTrue($subscription->isActive());
        $this->assertNotNull($subscription->current_period_end);
        $this->assertGreaterThan(now(), $subscription->current_period_end);
    }

    #[Test]
    public function command_cancels_subscription_marked_for_cancellation(): void
    {
        $subscription = $this->subscription([
            'current_period_end' => now()->subDay(),
            'notes' => json_encode(['cancel_at_period_end' => true]),
        ]);

        $this->artisan('subscriptions:process-period-ends')->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Cancelled, $subscription->status);
        $this->assertNull($subscription->notes);
    }

    #[Test]
    public function command_expires_subscription_with_no_pending_action(): void
    {
        $subscription = $this->subscription(['current_period_end' => now()->subDay()]);

        $this->artisan('subscriptions:process-period-ends')->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Restricted, $subscription->status);
    }

    #[Test]
    public function command_expires_ended_trial_subscriptions(): void
    {
        $subscription = $this->subscription([
            'current_period_end' => null,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:process-period-ends')->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Restricted, $subscription->status);
    }

    #[Test]
    public function command_leaves_active_subscriptions_untouched(): void
    {
        $subscription = $this->subscription(['current_period_end' => now()->addMonth()]);

        $this->artisan('subscriptions:process-period-ends')->assertSuccessful();

        $subscription->refresh();
        $this->assertTrue($subscription->isActive());
    }
}
