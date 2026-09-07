<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Database\Factories\ProductImportRowFactory;
use Modules\Catalog\Enums\ImportRowStatus;

/**
 * The recorded outcome of a single data row in a bulk import (Phase 3.3).
 *
 * @property int $id
 * @property int $product_import_batch_id
 * @property int $row_number
 * @property ImportRowStatus $status
 * @property int|null $product_id
 * @property array<string, list<string>>|null $errors
 */
class ProductImportRow extends Model
{
    /** @use HasFactory<ProductImportRowFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportRowStatus::class,
            'errors' => 'array',
        ];
    }

    protected static function newFactory(): ProductImportRowFactory
    {
        return ProductImportRowFactory::new();
    }

    /**
     * @return BelongsTo<ProductImportBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductImportBatch::class, 'product_import_batch_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
