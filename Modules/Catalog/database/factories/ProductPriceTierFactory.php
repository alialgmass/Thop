<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\ProductPriceTier;

/**
 * @extends Factory<ProductPriceTier>
 */
class ProductPriceTierFactory extends Factory
{
    protected $model = ProductPriceTier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'min_qty' => fake()->numberBetween(1, 100),
            'unit_price' => fake()->randomFloat(2, 1, 400),
        ];
    }
}
