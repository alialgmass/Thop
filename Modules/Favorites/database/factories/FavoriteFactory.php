<?php

namespace Modules\Favorites\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
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
            'favoritable_type' => 'product',
            'favoritable_id' => Product::factory(),
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(['favoritable_type' => 'product', 'favoritable_id' => $product->getKey()]);
    }
}
