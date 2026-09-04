<?php

namespace Modules\Favorites\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Favorites\Models\Favorite;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favoritable_type' => FavoritableType::Product->value,
            'favoritable_id' => Product::factory(),
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state([
            'favoritable_type' => FavoritableType::Product->value,
            'favoritable_id' => $product->getKey(),
        ]);
    }

    public function forSupplier(BusinessAccount $supplier): static
    {
        return $this->state([
            'favoritable_type' => FavoritableType::Supplier->value,
            'favoritable_id' => $supplier->getKey(),
        ]);
    }
}
