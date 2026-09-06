<?php

namespace Modules\Businesses\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * US-INQ-05 / Open Decision #4 — a buyer sees a seller's phone/WhatsApp only
 * when the seller's active plan grants `contact_info_visible` (default off).
 */
class ContactVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private BusinessAccount $seller;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = BusinessAccount::factory()
            ->for(User::factory()->importer()->create(), 'owner')
            ->create(['contact_channels' => [['type' => 'whatsapp', 'value' => '+201000000009']]]);

        $this->buyer = User::factory()->wholesaler()->create();
    }

    private function grantContactVisibility(string $value = 'true'): void
    {
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'contact_info_visible', 'value' => $value]);
        Subscription::create([
            'business_account_id' => $this->seller->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    #[Test]
    public function a_buyer_does_not_see_contact_channels_by_default(): void
    {
        $this->actingAs($this->buyer)
            ->getJson("/api/v1/businesses/{$this->seller->id}")
            ->assertOk()
            ->assertJsonMissingPath('body.business.contact_channels');
    }

    #[Test]
    public function a_buyer_sees_contact_channels_when_the_plan_grants_it(): void
    {
        $this->grantContactVisibility('true');

        $this->actingAs($this->buyer)
            ->getJson("/api/v1/businesses/{$this->seller->id}")
            ->assertOk()
            ->assertJsonPath('body.business.contact_channels.0.value', '+201000000009');
    }

    #[Test]
    public function the_grant_does_nothing_without_an_active_subscription(): void
    {
        $this->grantContactVisibility('true');
        $this->seller->subscriptions()->update(['status' => 'cancelled']);

        $this->actingAs($this->buyer)
            ->getJson("/api/v1/businesses/{$this->seller->id}")
            ->assertOk()
            ->assertJsonMissingPath('body.business.contact_channels');
    }

    #[Test]
    public function the_owner_always_sees_their_own_contact_channels(): void
    {
        $this->actingAs($this->seller->owner)
            ->getJson("/api/v1/businesses/{$this->seller->id}")
            ->assertOk()
            ->assertJsonPath('body.business.contact_channels.0.value', '+201000000009');
    }
}
