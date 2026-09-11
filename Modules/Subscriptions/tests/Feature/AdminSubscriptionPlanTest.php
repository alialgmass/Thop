<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionEntitlementSnapshot;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin subscription-plan management + apply-to-existing (Phase 9 · T4,
 * US-SUB-05, US-ADM-04).
 */
class AdminSubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    private function activeSubscription(SubscriptionPlan $plan): Subscription
    {
        $business = BusinessAccount::factory()
            ->for(User::factory()->importer()->create(), 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        return Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    #[Test]
    public function a_non_admin_cannot_reach_plan_management(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/subscription-plans')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_creates_a_plan_with_entitlements(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/subscription-plans', [
                'account_type' => 'importer',
                'name' => 'Pro',
                'price' => 499.99,
                'trial_days' => 14,
                'entitlements' => [
                    ['key' => 'product_limit', 'value' => '50'],
                ],
            ])
            ->assertOk();

        $plan = SubscriptionPlan::where('name', 'Pro')->firstOrFail();
        $this->assertSame(14, $plan->trial_days);
        $this->assertDatabaseHas('subscription_entitlements', [
            'plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '50',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'plan.created', 'auditable_id' => $plan->id,
        ]);
    }

    #[Test]
    public function a_plain_plan_edit_leaves_existing_subscriptions_entitlements_untouched(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '10']);
        $subscription = $this->activeSubscription($plan);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/subscription-plans/{$plan->id}", [
                'entitlements' => [['key' => 'product_limit', 'value' => '999']],
            ])
            ->assertOk();

        $this->assertDatabaseHas('subscription_entitlements', [
            'plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '999',
        ]);
        // The plan changed, but the existing subscriber's snapshot did not.
        $this->assertDatabaseHas('subscription_entitlement_snapshots', [
            'subscription_id' => $subscription->id, 'key' => 'product_limit', 'value' => '10',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'plan.updated', 'auditable_id' => $plan->id,
        ]);
    }

    #[Test]
    public function apply_to_existing_without_confirm_changes_nothing(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '999']);
        $subscription = $this->activeSubscription($plan);
        SubscriptionEntitlementSnapshot::where('subscription_id', $subscription->id)
            ->where('key', 'product_limit')->update(['value' => '10']);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/subscription-plans/{$plan->id}/apply-to-existing")
            ->assertStatus(400);

        $this->assertDatabaseHas('subscription_entitlement_snapshots', [
            'subscription_id' => $subscription->id, 'key' => 'product_limit', 'value' => '10',
        ]);
    }

    #[Test]
    public function apply_to_existing_with_confirm_updates_every_active_subscription_and_is_audited(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '10']);
        $subscription = $this->activeSubscription($plan);

        // Edit the plan (non-retroactive) then force-apply it.
        SubscriptionEntitlement::where('plan_id', $plan->id)->update(['value' => '999']);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/subscription-plans/{$plan->id}/apply-to-existing", ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('body.subscriptions_updated', 1);

        $this->assertDatabaseHas('subscription_entitlement_snapshots', [
            'subscription_id' => $subscription->id, 'key' => 'product_limit', 'value' => '999',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'plan.applied_to_existing', 'auditable_id' => $plan->id,
        ]);
    }

    #[Test]
    public function apply_to_existing_does_not_touch_a_cancelled_subscription_on_the_same_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '10']);
        $subscription = $this->activeSubscription($plan);
        $subscription->update(['status' => 'cancelled']);

        SubscriptionEntitlement::where('plan_id', $plan->id)->update(['value' => '999']);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/subscription-plans/{$plan->id}/apply-to-existing", ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('body.subscriptions_updated', 0);

        $this->assertDatabaseHas('subscription_entitlement_snapshots', [
            'subscription_id' => $subscription->id, 'key' => 'product_limit', 'value' => '10',
        ]);
    }
}
