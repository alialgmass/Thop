<?php

namespace Modules\Comparison\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ComparisonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->wholesaler()->create();
    }

    #[Test]
    public function it_compares_products_side_by_side(): void
    {
        $a = Product::factory()->published()->create(['price' => 40]);
        $b = Product::factory()->published()->create(['price' => null, 'price_on_contact' => true]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/compare?type=product&ids={$a->id},{$b->id}")
            ->assertOk()
            ->assertJsonPath('body.type', 'product')
            ->assertJsonCount(2, 'body.items')
            ->assertJsonPath('body.items.1.price_on_contact', true);
    }

    #[Test]
    public function it_compares_suppliers(): void
    {
        $a = BusinessAccount::factory()->create();
        $b = BusinessAccount::factory()->verified()->create();

        $this->actingAs($this->user)
            ->getJson("/api/v1/compare?type=supplier&ids={$a->id},{$b->id}")
            ->assertOk()
            ->assertJsonCount(2, 'body.items');
    }

    #[Test]
    public function comparing_five_items_is_rejected_with_a_clear_message(): void
    {
        $ids = Product::factory()->count(5)->published()->create()->pluck('id')->implode(',');

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/compare?type=product&ids={$ids}")
            ->assertStatus(400);

        $this->assertStringContainsString('4', $response->json('body.ids.0') ?? $response->json('message'));
    }

    #[Test]
    public function non_visible_products_are_dropped_and_reported_as_missing(): void
    {
        $visible = Product::factory()->published()->create();
        $draft = Product::factory()->draft()->create();

        $this->actingAs($this->user)
            ->getJson("/api/v1/compare?type=product&ids={$visible->id},{$draft->id}")
            ->assertOk()
            ->assertJsonCount(1, 'body.items')
            ->assertJsonPath('body.missing_ids', [$draft->id]);
    }

    #[Test]
    public function a_suspended_suppliers_profile_is_not_comparable(): void
    {
        $ok = BusinessAccount::factory()->create();
        $banned = BusinessAccount::factory()->create();
        $banned->owner->update(['status' => 'suspended']);

        $this->actingAs($this->user)
            ->getJson("/api/v1/compare?type=supplier&ids={$ok->id},{$banned->id}")
            ->assertOk()
            ->assertJsonCount(1, 'body.items')
            ->assertJsonPath('body.missing_ids', [$banned->id]);
    }

    #[Test]
    public function guests_cannot_compare(): void
    {
        $this->getJson('/api/v1/compare?type=product&ids=1')->assertStatus(401);
    }
}
