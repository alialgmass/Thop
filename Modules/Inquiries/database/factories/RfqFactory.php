<?php

namespace Modules\Inquiries\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Rfq;

/**
 * @extends Factory<Rfq>
 */
class RfqFactory extends Factory
{
    protected $model = Rfq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquiry_id' => Inquiry::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'color_id' => null,
            'needed_by_date' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
        ];
    }
}
