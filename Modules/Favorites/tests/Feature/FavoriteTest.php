<?php

namespace Modules\Favorites\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Favorites\Models\Favorite;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->wholesaler()->create();
    }

    #[Test]
    public function a_user_saves_a_product(): void
    {
        $product = Product::factory()->published()->create();

        $this->actingAs($this->user)
            ->postJson('/api/v1/favorites', ['type' => 'product', 'id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('body.favorite.type', 'product')
            ->assertJsonPath('body.favorite.favoritable_id', $product->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'favoritable_type' => 'product',
            'favoritable_id' => $product->id,
        ]);
    }

    #[Test]
    public function a_user_saves_a_supplier(): void
    {
        $supplier = BusinessAccount::factory()->create();

        $this->actingAs($this->user)
            ->postJson('/api/v1/favorites', ['type' => 'supplier', 'id' => $supplier->id])
            ->assertCreated()
            ->assertJsonPath('body.favorite.type', 'supplier');
    }

    #[Test]
    public function favoriting_the_same_item_twice_does_not_create_a_duplicate(): void
    {
        $product = Product::factory()->published()->create();
        $payload = ['type' => 'product', 'id' => $product->id];

        $this->actingAs($this->user)->postJson('/api/v1/favorites', $payload)->assertCreated();
        $this->actingAs($this->user)->postJson('/api/v1/favorites', $payload)->assertOk();

        $this->assertDatabaseCount('favorites', 1);
    }

    #[Test]
    public function favoriting_a_missing_target_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/favorites', ['type' => 'product', 'id' => 999999])
            ->assertStatus(404);
    }

    #[Test]
    public function an_unknown_type_is_a_validation_error(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/favorites', ['type' => 'banana', 'id' => 1])
            ->assertStatus(400);
    }

    #[Test]
    public function the_list_is_scoped_to_the_current_user_and_filterable_by_type(): void
    {
        $product = Product::factory()->published()->create();
        $supplier = BusinessAccount::factory()->create();

        Favorite::factory()->create(['user_id' => $this->user->id, 'favoritable_type' => 'product', 'favoritable_id' => $product->id]);
        Favorite::factory()->create(['user_id' => $this->user->id, 'favoritable_type' => 'supplier', 'favoritable_id' => $supplier->id]);
        Favorite::factory()->create(['favoritable_type' => 'product', 'favoritable_id' => $product->id]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/favorites')
            ->assertOk()
            ->assertJsonCount(2, 'body.favorites.data');

        $this->actingAs($this->user)
            ->getJson('/api/v1/favorites?type=supplier')
            ->assertOk()
            ->assertJsonCount(1, 'body.favorites.data')
            ->assertJsonPath('body.favorites.data.0.type', 'supplier');
    }

    #[Test]
    public function a_user_removes_their_own_favorite(): void
    {
        $favorite = Favorite::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/favorites/{$favorite->id}")
            ->assertOk();

        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
    }

    #[Test]
    public function removing_someone_elses_favorite_is_forbidden(): void
    {
        $favorite = Favorite::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/favorites/{$favorite->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('favorites', ['id' => $favorite->id]);
    }

    #[Test]
    public function guests_cannot_use_favorites(): void
    {
        $this->getJson('/api/v1/favorites')->assertStatus(401);
    }
}
