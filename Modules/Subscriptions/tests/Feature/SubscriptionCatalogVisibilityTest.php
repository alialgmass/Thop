<?php

namespace Modules\Subscriptions\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BR-SUB-03 — when a seller's subscription lapses, their published products stop
 * being buyer-visible (search, catalog, comparison, detail) but are never
 * deleted; they reappear on renewal.
 */
class SubscriptionCatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private BusinessAccount $business;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = BusinessAccount::factory()
            ->for(User::factory()->importer()->create(), 'owner')
            ->create();

        $this->product = Product::factory()->for($this->business)->published()->create();
    }

    private function subscribe(string $status, ?\DateTimeInterface $periodEnd = null): Subscription
    {
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);

        return Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'current_period_end' => $periodEnd ?? now()->addMonth(),
        ]);
    }

    private function isBuyerVisible(): bool
    {
        return Product::query()->buyerVisible()->whereKey($this->product->id)->exists();
    }

    #[Test]
    public function a_business_that_never_subscribed_keeps_its_products_visible(): void
    {
        $this->assertTrue($this->isBuyerVisible());
    }

    #[Test]
    public function an_active_subscription_keeps_products_visible(): void
    {
        $this->subscribe('active');

        $this->assertTrue($this->isBuyerVisible());
    }

    #[Test]
    public function a_restricted_subscription_hides_the_products_without_deleting_them(): void
    {
        $subscription = $this->subscribe('active');
        $subscription->markExpired(); // -> Restricted

        $this->assertFalse($this->isBuyerVisible());
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'status' => 'published',
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function a_subscription_whose_paid_period_has_elapsed_hides_the_products(): void
    {
        $this->subscribe('active', now()->subDay());

        $this->assertFalse($this->isBuyerVisible());
    }

    #[Test]
    public function renewing_after_a_lapse_restores_visibility(): void
    {
        $lapsed = $this->subscribe('active', now()->subDay());
        $this->assertFalse($this->isBuyerVisible());

        $lapsed->update(['status' => 'active', 'current_period_end' => now()->addMonth()]);

        $this->assertTrue($this->isBuyerVisible());
    }

    #[Test]
    public function an_old_expired_subscription_alongside_a_new_active_one_is_visible(): void
    {
        $this->subscribe('cancelled', now()->subMonth());
        $this->subscribe('active');

        $this->assertTrue($this->isBuyerVisible());
    }

    #[Test]
    public function the_seller_still_sees_their_own_products_after_a_lapse(): void
    {
        $this->subscribe('active')->markExpired();

        $this->actingAs($this->business->owner)
            ->getJson('/api/v1/products/mine')
            ->assertOk()
            ->assertJsonPath('body.products.data.0.id', $this->product->id);
    }

    #[Test]
    public function a_lapsed_sellers_product_detail_returns_404_to_buyers(): void
    {
        $this->subscribe('active')->markExpired();

        $this->getJson("/api/v1/products/{$this->product->id}")->assertStatus(404);
    }
}
