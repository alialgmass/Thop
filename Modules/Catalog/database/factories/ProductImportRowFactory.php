<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Enums\ImportRowStatus;
use Modules\Catalog\Models\ProductImportBatch;
use Modules\Catalog\Models\ProductImportRow;

/**
 * @extends Factory<ProductImportRow>
 */
class ProductImportRowFactory extends Factory
{
    protected $model = ProductImportRow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_import_batch_id' => ProductImportBatch::factory(),
            'row_number' => fake()->numberBetween(2, 200),
            'status' => ImportRowStatus::Imported,
            'product_id' => null,
            'errors' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ImportRowStatus::Failed,
            'errors' => ['name_ar' => ['The name ar field is required.']],
        ]);
    }

    public function limitRejected(): static
    {
        return $this->state([
            'status' => ImportRowStatus::LimitRejected,
            'errors' => ['product_limit' => ['You have reached the product limit on your current plan.']],
        ]);
    }
}
