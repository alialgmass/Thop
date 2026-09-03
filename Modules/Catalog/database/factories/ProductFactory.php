<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Product;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_account_id' => BusinessAccount::factory(),
            'fabric_type_id' => FabricType::factory(),
            'material_id' => Material::factory(),
            'governorate_id' => Governorate::factory(),
            'name_ar' => 'قماش '.fake()->unique()->numerify('######'),
            'price' => fake()->randomFloat(2, 1, 500),
            'price_on_contact' => false,
            'quantity_available' => fake()->numberBetween(1, 1000),
            'status' => ProductStatus::Draft,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft]);
    }

    public function pendingReview(): static
    {
        return $this->state(['status' => ProductStatus::PendingReview]);
    }

    public function published(): static
    {
        return $this->state(['status' => ProductStatus::Published]);
    }

    public function hidden(): static
    {
        return $this->state(['status' => ProductStatus::Hidden]);
    }

    public function unavailable(): static
    {
        return $this->state(['status' => ProductStatus::Unavailable]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ProductStatus::Rejected]);
    }
}
