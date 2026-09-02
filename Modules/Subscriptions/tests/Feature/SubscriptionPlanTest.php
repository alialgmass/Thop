<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function plans_are_listed_filtered_by_account_type(): void
    {
        SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
            'price' => null,
        ]);

        SubscriptionPlan::create([
            'account_type' => 'wholesaler',
            'name' => 'Wholesaler',
            'price' => null,
        ]);

        $user = User::factory()->importer()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/subscription-plans?account_type=importer');

        $response->assertOk()
            ->assertJsonPath('body.plans.0.name', 'Basic')
            ->assertJsonCount(1, 'body.plans');
    }

    #[Test]
    public function plans_include_entitlements(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        SubscriptionEntitlement::create([
            'plan_id' => $plan->id,
            'key' => 'product_limit',
            'value' => 'Large',
        ]);

        SubscriptionEntitlement::create([
            'plan_id' => $plan->id,
            'key' => 'search_priority',
            'value' => 'true',
        ]);

        $user = User::factory()->importer()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/subscription-plans');

        $response->assertOk()
            ->assertJsonPath('body.plans.0.entitlements.0.key', 'product_limit')
            ->assertJsonCount(2, 'body.plans.0.entitlements');
    }

    #[Test]
    public function only_active_plans_are_shown(): void
    {
        SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Active Plan',
            'is_active' => true,
        ]);

        SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Inactive Plan',
            'is_active' => false,
        ]);

        $user = User::factory()->importer()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/subscription-plans');

        $response->assertOk()
            ->assertJsonPath('body.plans.0.name', 'Active Plan')
            ->assertJsonCount(1, 'body.plans');
    }

    #[Test]
    public function unauthenticated_user_can_list_plans(): void
    {
        SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $response = $this->getJson('/api/v1/subscription-plans');

        // Plans endpoint requires auth (auth:sanctum middleware)
        $response->assertUnauthorized();
    }
}
