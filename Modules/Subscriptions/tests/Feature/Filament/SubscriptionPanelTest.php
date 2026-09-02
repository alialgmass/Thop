<?php

namespace Modules\Subscriptions\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use Modules\Subscriptions\Filament\Resources\Subscriptions\Pages\ViewSubscription;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPanelTest extends TestCase
{
    use RefreshDatabase;

    private function activeSubscription(): Subscription
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()
            ->for($owner, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);

        $plan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
        ]);

        return Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    #[Test]
    public function a_non_admin_cannot_open_the_subscriptions_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/subscriptions')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_subscriptions_in_the_list(): void
    {
        $subscription = $this->activeSubscription();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListSubscriptions::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$subscription]);
    }

    #[Test]
    public function an_admin_can_grant_a_trial_from_the_view_page(): void
    {
        $subscription = $this->activeSubscription();
        // Grant Trial is only available on a non-active (lapsed) subscription,
        // so it never overwrites a live paid period.
        $subscription->update(['status' => SubscriptionStatus::Cancelled]);

        $trialPlan = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ViewSubscription::class, ['record' => $subscription->getKey()])
            ->callAction('grantTrial', [
                'plan_id' => $trialPlan->id,
                'trial_ends_at' => now()->addDays(14)->toDateString(),
            ]);

        $this->assertSame(SubscriptionStatus::Active, $subscription->refresh()->status);
        $this->assertSame($trialPlan->id, $subscription->plan_id);
        $this->assertNotNull($subscription->trial_ends_at);
    }

    #[Test]
    public function an_admin_can_extend_the_period_from_the_view_page(): void
    {
        $subscription = $this->activeSubscription();
        $newEnd = now()->addMonths(2)->toDateString();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewSubscription::class, ['record' => $subscription->getKey()])
            ->callAction('extendPeriod', [
                'new_period_end' => $newEnd,
            ]);

        $this->assertEquals(
            Carbon::parse($newEnd)->startOfDay(),
            $subscription->refresh()->current_period_end->startOfDay()
        );
    }

    #[Test]
    public function an_admin_can_cancel_an_active_subscription(): void
    {
        $subscription = $this->activeSubscription();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewSubscription::class, ['record' => $subscription->getKey()])
            ->callAction('cancel');

        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->refresh()->status);
    }
}
