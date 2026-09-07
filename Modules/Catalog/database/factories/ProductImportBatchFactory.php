<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Enums\ImportBatchStatus;
use Modules\Catalog\Models\ProductImportBatch;

/**
 * @extends Factory<ProductImportBatch>
 */
class ProductImportBatchFactory extends Factory
{
    protected $model = ProductImportBatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_account_id' => BusinessAccount::factory(),
            'original_filename' => 'products.csv',
            'stored_path' => 'product-imports/'.fake()->uuid().'.csv',
            'status' => ImportBatchStatus::Completed,
        ];
    }
}
