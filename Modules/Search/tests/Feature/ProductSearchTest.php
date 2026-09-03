<?php

namespace Modules\Search\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private BusinessAccount $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = $this->makeBusiness();
    }

    private function makeBusiness(): BusinessAccount
    {
        $user = User::factory()->importer()->create();

        return BusinessAccount::factory()->for($user, 'owner')->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedProduct(array $attributes = [], ?BusinessAccount $business = null): Product
    {
        return Product::factory()
            ->for($business ?? $this->business)
            ->published()
            ->create(array_merge([
                'fabric_type_id' => FabricType::factory(),
                'material_id' => Material::factory(),
                'governorate_id' => Governorate::factory(),
            ], $attributes));
    }

    private function makeFeatured(BusinessAccount $business): void
    {
        $plan = SubscriptionPlan::create(['account_type' => 'importer', 'name' => 'Pro']);
        SubscriptionEntitlement::create(['plan_id' => $plan->id, 'key' => 'featured_products', 'value' => 'true']);

        Subscription::create([
            'business_account_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    #[Test]
    public function it_returns_only_published_visible_products(): void
    {
        $published = $this->publishedProduct(['name_ar' => 'قطن أبيض']);
        $this->publishedProduct(['name_ar' => 'كتان أزرق'])->update(['status' => 'draft']);
        Product::factory()->for($this->business)->pendingReview()->create();
        Product::factory()->for($this->business)->hidden()->create();
        Product::factory()->for($this->business)->unavailable()->create();
        $deleted = $this->publishedProduct();
        $deleted->delete();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $published->id);
    }

    #[Test]
    public function products_from_a_suspended_supplier_are_hidden(): void
    {
        $this->publishedProduct(['name_ar' => 'قماش ظاهر']);

        $banned = $this->makeBusiness();
        $banned->owner->update(['status' => 'suspended']);
        $this->publishedProduct(['name_ar' => 'قماش مخفي'], $banned);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data');
    }

    #[Test]
    public function free_text_matches_a_common_spelling_variant(): void
    {
        $match = $this->publishedProduct(['name_ar' => 'قماش قطنية بيضاء']);
        $this->publishedProduct(['name_ar' => 'حرير أحمر']);

        // taa-marbuta vs haa, plus tashkeel on the query
        $this->getJson('/api/v1/products?search='.urlencode('قطنيه'))
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $match->id);
    }

    #[Test]
    public function english_synonym_matches(): void
    {
        $match = $this->publishedProduct(['name_ar' => 'خامة', 'name_en' => 'cotton blend']);
        $this->publishedProduct(['name_ar' => 'خامة', 'name_en' => 'pure silk']);

        $this->getJson('/api/v1/products?search=coton')
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $match->id);
    }

    #[Test]
    public function filters_combine_as_an_intersection(): void
    {
        $fabric = FabricType::factory()->create();
        $gov = Governorate::factory()->create();
        $color = Color::factory()->create();

        $wanted = $this->publishedProduct([
            'fabric_type_id' => $fabric->id,
            'governorate_id' => $gov->id,
            'price' => 100,
            'quantity_available' => 5,
        ]);
        $wanted->colors()->attach($color->id);

        // right fabric, wrong governorate
        $this->publishedProduct(['fabric_type_id' => $fabric->id, 'governorate_id' => Governorate::factory()->create()->id]);
        // right governorate, wrong price band
        $this->publishedProduct(['governorate_id' => $gov->id, 'price' => 999]);

        $this->getJson('/api/v1/products?'.http_build_query([
            'filters' => [
                'fabric_type_id' => $fabric->id,
                'governorate_id' => $gov->id,
                'color_id' => [$color->id],
                'price_min' => 50,
                'price_max' => 200,
                'availability' => true,
            ],
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $wanted->id);
    }

    #[Test]
    public function moq_max_filter_includes_null_moq_products(): void
    {
        $noMoq = $this->publishedProduct(['moq' => null]);
        $lowMoq = $this->publishedProduct(['moq' => 10]);
        $this->publishedProduct(['moq' => 500]);

        $response = $this->getJson('/api/v1/products?filters[moq_max]=50')->assertOk();

        $ids = collect($response->json('body.products.data'))->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$noMoq->id, $lowMoq->id])->sort()->values()->all(), $ids);
    }

    #[Test]
    public function color_and_width_filters_narrow_the_result_set(): void
    {
        $blue = Color::factory()->create();
        $red = Color::factory()->create();

        $wanted = $this->publishedProduct(['width_cm' => 150]);
        $wanted->colors()->attach($blue->id);

        $wrongColor = $this->publishedProduct(['width_cm' => 150]);
        $wrongColor->colors()->attach($red->id);

        $wrongWidth = $this->publishedProduct(['width_cm' => 300]);
        $wrongWidth->colors()->attach($blue->id);

        $this->getJson('/api/v1/products?'.http_build_query([
            'filters' => ['color_id' => [$blue->id], 'width_cm_min' => 100, 'width_cm_max' => 200],
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $wanted->id);
    }

    #[Test]
    public function availability_filter_excludes_out_of_stock_products(): void
    {
        $inStock = $this->publishedProduct(['quantity_available' => 20]);
        $this->publishedProduct(['quantity_available' => 0]);

        $this->getJson('/api/v1/products?filters[availability]=1')
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $inStock->id);
    }

    #[Test]
    public function sort_newest_returns_the_most_recent_first(): void
    {
        $old = $this->publishedProduct();
        $old->update(['created_at' => now()->subDays(3)]);
        $new = $this->publishedProduct();

        $this->getJson('/api/v1/products?sort=newest')
            ->assertOk()
            ->assertJsonPath('body.products.data.0.id', $new->id)
            ->assertJsonPath('body.products.data.1.id', $old->id);
    }

    #[Test]
    public function sort_supplier_rating_degrades_to_verified_first(): void
    {
        $verified = $this->makeBusiness();
        $verified->update(['verification_status' => 'verified']);
        $verifiedProduct = $this->publishedProduct([], $verified);
        $verifiedProduct->update(['created_at' => now()->subDays(2)]);

        $plainNewer = $this->publishedProduct();

        $this->getJson('/api/v1/products?sort=supplier_rating')
            ->assertOk()
            ->assertJsonPath('body.products.data.0.id', $verifiedProduct->id)
            ->assertJsonPath('body.products.data.1.id', $plainNewer->id);
    }

    #[Test]
    public function an_unknown_filter_id_yields_an_empty_result_not_an_error(): void
    {
        $this->publishedProduct();

        $this->getJson('/api/v1/products?filters[fabric_type_id]=999999')
            ->assertOk()
            ->assertJsonCount(0, 'body.products.data');
    }

    #[Test]
    public function sort_by_price_orders_ascending_and_keeps_filters(): void
    {
        $gov = Governorate::factory()->create();
        $cheap = $this->publishedProduct(['governorate_id' => $gov->id, 'price' => 10]);
        $mid = $this->publishedProduct(['governorate_id' => $gov->id, 'price' => 50]);
        $this->publishedProduct(['governorate_id' => Governorate::factory()->create()->id, 'price' => 1]);

        $this->getJson('/api/v1/products?sort=price_asc&filters[governorate_id]='.$gov->id)
            ->assertOk()
            ->assertJsonCount(2, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $cheap->id)
            ->assertJsonPath('body.products.data.1.id', $mid->id);
    }

    #[Test]
    public function featured_products_outrank_a_newer_non_featured_product(): void
    {
        $featuredBusiness = $this->makeBusiness();
        $this->makeFeatured($featuredBusiness);

        $featuredOld = $this->publishedProduct(['name_ar' => 'قديم مميز'], $featuredBusiness);
        $featuredOld->update(['created_at' => now()->subDays(5)]);

        $plainNew = $this->publishedProduct(['name_ar' => 'جديد عادي']);

        $response = $this->getJson('/api/v1/products?sort=newest')->assertOk();

        $this->assertSame($featuredOld->id, $response->json('body.products.data.0.id'));
        $this->assertTrue($response->json('body.products.data.0.featured'));
        $this->assertSame($plainNew->id, $response->json('body.products.data.1.id'));
        $this->assertFalse($response->json('body.products.data.1.featured'));
    }

    #[Test]
    public function the_featured_boost_disappears_when_the_subscription_lapses(): void
    {
        $featuredBusiness = $this->makeBusiness();
        $this->makeFeatured($featuredBusiness);
        $featuredBusiness->subscription->update(['status' => 'cancelled']);

        $featuredOld = $this->publishedProduct([], $featuredBusiness);
        $featuredOld->update(['created_at' => now()->subDays(5)]);
        $plainNew = $this->publishedProduct();

        $response = $this->getJson('/api/v1/products?sort=newest')->assertOk();

        $this->assertSame($plainNew->id, $response->json('body.products.data.0.id'));
        $this->assertFalse($response->json('body.products.data.0.featured'));
    }

    #[Test]
    public function a_zero_result_search_is_logged_and_returns_a_friendly_empty_state(): void
    {
        $this->publishedProduct(['name_ar' => 'قطن']);

        $this->getJson('/api/v1/products?search='.urlencode('بوليستر غير موجود'))
            ->assertOk()
            ->assertJsonCount(0, 'body.products.data');

        $this->assertDatabaseHas('search_logs', [
            'result_count' => 0,
            'context' => 'product',
        ]);
    }

    #[Test]
    public function a_matched_search_is_not_logged(): void
    {
        $this->publishedProduct(['name_ar' => 'قطن ناعم']);

        $this->getJson('/api/v1/products?search='.urlencode('قطن'))->assertOk();

        $this->assertDatabaseCount('search_logs', 0);
    }

    #[Test]
    public function an_out_of_range_page_returns_an_empty_list_not_an_error(): void
    {
        $this->publishedProduct();

        $this->getJson('/api/v1/products?page=99')
            ->assertOk()
            ->assertJsonCount(0, 'body.products.data');
    }

    #[Test]
    public function the_public_product_detail_hides_internal_fields_and_404s_for_non_visible(): void
    {
        $product = $this->publishedProduct(['name_ar' => 'تفصيلة']);

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('body.product.id', $product->id)
            ->assertJsonMissingPath('body.product.status')
            ->assertJsonMissingPath('body.product.rejection_reason')
            ->assertJsonPath('body.product.supplier.id', $this->business->id);

        $pending = Product::factory()->for($this->business)->pendingReview()->create();

        $this->getJson("/api/v1/products/{$pending->id}")->assertStatus(404);
    }

    #[Test]
    public function the_supplier_catalog_is_scoped_and_filterable(): void
    {
        $mine = $this->publishedProduct(['name_ar' => 'منتجي']);
        $other = $this->makeBusiness();
        $this->publishedProduct(['name_ar' => 'منتج غيري'], $other);

        $this->getJson("/api/v1/businesses/{$this->business->id}/catalog")
            ->assertOk()
            ->assertJsonCount(1, 'body.products.data')
            ->assertJsonPath('body.products.data.0.id', $mine->id);
    }
}
