<?php

namespace Modules\Search\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Search\Models\FeaturedPlacement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin featured-placement curation (Phase 9 · T5, US-SRC-10, BR-SRC-01).
 */
class AdminFeaturedPlacementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_manage_featured_placements(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/featured')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_creates_a_placement_for_a_product(): void
    {
        $product = Product::factory()->published()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/featured', [
                'type' => 'product', 'featurable_id' => $product->id, 'slot' => 'homepage_top',
            ])
            ->assertOk();

        $this->assertDatabaseHas('featured_placements', [
            'featurable_type' => 'product', 'featurable_id' => $product->id, 'slot' => 'homepage_top',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'featured.placed',
        ]);
    }

    #[Test]
    public function creating_a_placement_for_a_nonexistent_item_404s(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/featured', [
                'type' => 'product', 'featurable_id' => 999999, 'slot' => 'homepage_top',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function featuring_the_same_item_in_the_same_slot_twice_is_rejected(): void
    {
        $product = Product::factory()->published()->create();
        FeaturedPlacement::factory()->create([
            'featurable_type' => 'product', 'featurable_id' => $product->id, 'slot' => 'homepage_top',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/featured', [
                'type' => 'product', 'featurable_id' => $product->id, 'slot' => 'homepage_top',
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('featured_placements', 1);
    }

    #[Test]
    public function the_same_item_can_hold_two_different_slots(): void
    {
        $product = Product::factory()->published()->create();
        FeaturedPlacement::factory()->create([
            'featurable_type' => 'product', 'featurable_id' => $product->id, 'slot' => 'homepage_top',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/featured', [
                'type' => 'product', 'featurable_id' => $product->id, 'slot' => 'category_spotlight',
            ])
            ->assertOk();

        $this->assertDatabaseCount('featured_placements', 2);
    }

    #[Test]
    public function an_admin_removes_a_placement(): void
    {
        $placement = FeaturedPlacement::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/featured/{$placement->id}")
            ->assertOk();

        $this->assertDatabaseMissing('featured_placements', ['id' => $placement->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'featured.removed', 'auditable_id' => $placement->id,
        ]);
    }

    #[Test]
    public function an_admin_curated_product_is_featured_with_no_plan_entitlement(): void
    {
        $product = Product::factory()->published()->create();
        FeaturedPlacement::factory()->create([
            'featurable_type' => 'product', 'featurable_id' => $product->id,
        ]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id, 'featured' => true]);
    }

    #[Test]
    public function a_placement_outside_its_date_window_is_not_featured(): void
    {
        $expiredProduct = Product::factory()->published()->create();
        FeaturedPlacement::factory()->expired()->create([
            'featurable_type' => 'product', 'featurable_id' => $expiredProduct->id,
        ]);

        $futureProduct = Product::factory()->published()->create();
        FeaturedPlacement::factory()->notYetStarted()->create([
            'featurable_type' => 'product', 'featurable_id' => $futureProduct->id,
        ]);

        $response = $this->getJson('/api/v1/products')->assertOk();

        $response->assertJsonFragment(['id' => $expiredProduct->id, 'featured' => false]);
        $response->assertJsonFragment(['id' => $futureProduct->id, 'featured' => false]);
    }

    #[Test]
    public function a_placement_for_a_supplier_features_that_supplier_not_its_products(): void
    {
        $business = BusinessAccount::factory()->verified()->create();
        $product = Product::factory()->published()->for($business)->create();
        FeaturedPlacement::factory()->create([
            'featurable_type' => 'supplier', 'featurable_id' => $business->id,
        ]);

        $this->getJson('/api/v1/businesses')
            ->assertOk()
            ->assertJsonFragment(['id' => $business->id, 'featured' => true]);

        // The product itself is not automatically featured by its supplier's placement.
        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id, 'featured' => false]);
    }

    #[Test]
    public function the_product_detail_endpoint_reflects_an_active_placement(): void
    {
        $product = Product::factory()->published()->create();
        FeaturedPlacement::factory()->create([
            'featurable_type' => 'product', 'featurable_id' => $product->id,
        ]);

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('body.product.featured', true);
    }
}
