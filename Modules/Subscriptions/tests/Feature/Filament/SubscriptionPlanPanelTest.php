<?php

namespace Modules\Subscriptions\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_the_plans_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/subscription-plans')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_plans_in_the_list(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListSubscriptionPlans::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$plan]);
    }

    #[Test]
    public function an_admin_can_create_a_plan_with_entitlements(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSubscriptionPlan::class)
            ->fillForm([
                'name' => 'Test Plan',
                'account_type' => 'importer',
                'entitlements' => [
                    ['key' => 'product_limit', 'value' => '50'],
                    ['key' => 'search_priority', 'value' => 'true'],
                ],
            ])
            ->call('create');

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Test Plan',
            'account_type' => 'importer',
        ]);

        $plan = SubscriptionPlan::where('name', 'Test Plan')->first();
        $this->assertDatabaseHas('subscription_entitlements', [
            'plan_id' => $plan->id,
            'key' => 'product_limit',
            'value' => '50',
        ]);
        $this->assertDatabaseHas('subscription_entitlements', [
            'plan_id' => $plan->id,
            'key' => 'search_priority',
            'value' => 'true',
        ]);
    }

    #[Test]
    public function an_admin_can_edit_a_plan_and_its_entitlements(): void
    {
        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        SubscriptionEntitlement::create([
            'plan_id' => $plan->id,
            'key' => 'product_limit',
            'value' => '10',
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditSubscriptionPlan::class, ['record' => $plan->getKey()])
            ->fillForm([
                'name' => 'Basic Pro',
                'entitlements' => [
                    ['key' => 'product_limit', 'value' => '100'],
                ],
            ])
            ->call('save');

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Basic Pro',
        ]);
        $this->assertDatabaseHas('subscription_entitlements', [
            'plan_id' => $plan->id,
            'key' => 'product_limit',
            'value' => '100',
        ]);
    }

    #[Test]
    public function plan_name_and_account_type_are_required(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSubscriptionPlan::class)
            ->fillForm([
                'name' => '',
                'account_type' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'account_type' => 'required',
            ]);
    }

    #[Test]
    public function resource_is_discoverable_in_panel(): void
    {
        $this->assertTrue(
            SubscriptionPlanResource::canViewAny()
        );
    }
}
