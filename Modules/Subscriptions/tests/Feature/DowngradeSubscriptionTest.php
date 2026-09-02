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

class DowngradeSubscriptionTest extends TestCase
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

        $proPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $this->subscription = Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addWeeks(3),
        ]);
    }

    #[Test]
    public function downgrade_preserves_current_entitlements_until_period_end(): void
    {
        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'downgrade',
                'plan_id' => $basicPlan->id,
            ]);

        $response->assertOk();

        // Subscription should still be active (not cancelled)
        $this->assertDatabaseHas('subscriptions', [
            'id' => $this->subscription->id,
            'status' => 'active',
        ]);

        // The downgrade should be scheduled (notes stored)
        $this->subscription->refresh();
        $notes = json_decode($this->subscription->notes, true);
        $this->assertEquals($basicPlan->id, $notes['pending_plan_id']);
    }

    #[Test]
    public function downgrade_does_not_truncate_paid_period(): void
    {
        $periodEnd = now()->addWeeks(2);

        $this->subscription->update(['current_period_end' => $periodEnd]);

        $basicPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'downgrade',
                'plan_id' => $basicPlan->id,
            ]);

        // Period end should NOT be changed
        $this->assertDatabaseHas('subscriptions', [
            'id' => $this->subscription->id,
            'current_period_end' => $periodEnd,
        ]);
    }

    #[Test]
    public function cancel_subscription_preserves_until_period_end(): void
    {
        $periodEnd = now()->addMonth();

        $this->subscription->update(['current_period_end' => $periodEnd]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/subscriptions/{$this->subscription->id}", [
                'action' => 'cancel',
            ]);

        $response->assertOk();

        // Should still be active until period end
        $this->assertDatabaseHas('subscriptions', [
            'id' => $this->subscription->id,
            'status' => 'active',
        ]);

        // Cancel flag stored in notes
        $this->subscription->refresh();
        $notes = json_decode($this->subscription->notes, true);
        $this->assertTrue($notes['cancel_at_period_end']);
    }
}
