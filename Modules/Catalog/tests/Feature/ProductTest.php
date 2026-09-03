<?php

namespace Modules\Catalog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Subscriptions\Services\EntitlementService;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessAccount $business;

    private FabricType $fabricType;

    private Material $material;

    private Governorate $governorate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()
            ->for($this->user, 'owner')
            ->create(['governorate_id' => Governorate::factory()->create()->id]);
        $this->fabricType = FabricType::factory()->create();
        $this->material = Material::factory()->create();
        $this->governorate = Governorate::factory()->create();

        $this->activeSubscription();
    }

    protected function activeSubscription(): Subscription
    {
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Basic']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'product_limit', 'value' => '100']);

        return Subscription::create([
            'business_account_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'قماش قطني',
            'fabric_type_id' => $this->fabricType->id,
            'material_id' => $this->material->id,
            'governorate_id' => $this->governorate->id,
            'width_cm' => 150,
            'weight_gsm' => 200,
            'unit' => 'per_meter',
            'price' => 45.50,
            'quantity_available' => 500,
            'moq' => 10,
            'colors' => [Color::factory()->create()->id],
        ], $overrides);
    }

    #[Test]
    public function a_seller_creates_a_product_which_enters_review(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('body.product.name_ar', 'قماش قطني')
            ->assertJsonPath('body.product.status', 'pending_review');

        $this->assertDatabaseHas('products', [
            'business_account_id' => $this->business->id,
            'name_ar' => 'قماش قطني',
            'status' => 'pending_review',
        ]);
    }

    #[Test]
    public function a_seller_can_save_a_draft(): void
    {
        $this->actingAs($this->user)->postJson(
            '/api/v1/products',
            $this->validPayload(['draft' => true]),
        )
            ->assertStatus(201)
            ->assertJsonPath('body.product.status', 'draft');
    }

    #[Test]
    public function missing_mandatory_fields_are_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/products', [
                'name_ar' => '',
                'fabric_type_id' => '',
                'price' => 10,
            ])
            ->assertStatus(400)
            ->assertJsonStructure(['body' => ['name_ar', 'fabric_type_id', 'material_id', 'governorate_id', 'unit', 'quantity_available']]);
    }

    #[Test]
    public function both_price_and_price_on_contact_are_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/products', $this->validPayload([
                'price' => 30,
                'price_on_contact' => true,
            ]))
            ->assertStatus(400)
            ->assertJsonStructure(['body' => ['price']]);
    }

    #[Test]
    public function neither_price_nor_price_on_contact_are_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/products', $this->validPayload([
                'price' => null,
                'price_on_contact' => false,
            ]))
            ->assertStatus(400)
            ->assertJsonStructure(['body' => ['price']]);
    }

    #[Test]
    public function a_wholesaler_is_forbidden_from_creating_products(): void
    {
        $wholesaler = User::factory()->wholesaler()->create();

        $this->actingAs($wholesaler)
            ->postJson('/api/v1/products', $this->validPayload())
            ->assertStatus(403);
    }

    #[Test]
    public function a_foreign_seller_cannot_view_update_or_delete_a_product(): void
    {
        $product = Product::factory()->for($this->business)->create();

        $foreign = User::factory()->importer()->create();
        BusinessAccount::factory()->for($foreign, 'owner')->create();

        $this->actingAs($foreign)
            ->getJson("/api/v1/products/mine/{$product->id}")
            ->assertStatus(403);

        $this->actingAs($foreign)
            ->patchJson("/api/v1/products/{$product->id}", ['name_ar' => 'Hacked'])
            ->assertStatus(403);

        $this->actingAs($foreign)
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(403);

        $this->assertNotSoftDeleted('products', ['business_account_id' => $this->business->id]);
    }

    #[Test]
    public function a_deleted_product_is_excluded_from_the_sellers_listing(): void
    {
        $kept = Product::factory()->for($this->business)->create();
        $removed = Product::factory()->for($this->business)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/products/{$removed->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $removed->id]);
        $this->assertNotSoftDeleted('products', ['id' => $kept->id]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/products/mine')
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $kept->id);
    }

    #[Test]
    public function product_creation_is_blocked_when_the_plan_limit_is_exhausted(): void
    {
        Product::factory()->count(100)->for($this->business)->create([
            'fabric_type_id' => $this->fabricType->id,
            'material_id' => $this->material->id,
            'governorate_id' => $this->governorate->id,
        ]);

        // Real creates bump this counter; factory inserts bypass it (BR-SEL-01).
        app(EntitlementService::class)
            ->incrementUsage($this->business, 'product_count', 100);

        $this->actingAs($this->user)
            ->postJson('/api/v1/products', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonStructure(['custom_code' => [], 'message' => []]);
    }

    #[Test]
    public function the_owner_can_transition_a_products_status(): void
    {
        $product = Product::factory()->for($this->business)->published()->create();

        $this->actingAs($this->user)
            ->patchJson("/api/v1/products/{$product->id}/status", ['status' => 'unavailable'])
            ->assertStatus(200)
            ->assertJsonPath('body.product.status', 'unavailable');
    }

    #[Test]
    public function the_public_catalog_returns_only_published_products(): void
    {
        Product::factory()->for($this->business)->published()->create();
        Product::factory()->for($this->business)->draft()->create();
        Product::factory()->for($this->business)->pendingReview()->create();

        $this->getJson("/api/v1/businesses/{$this->business->id}/catalog")
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data');
    }
}
