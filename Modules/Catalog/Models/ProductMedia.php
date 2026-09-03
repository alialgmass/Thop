<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single media file (image, or video in R2) attached to a product (US-SEL-03).
 * The binary lives on the public disk (S3/CDN); this row stores metadata only.
 *
 * @property int $id
 * @property int $product_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property string $original_name
 * @property string $type
 * @property int $sort_order
 */
class ProductMedia extends Model
{
    /** @use HasFactory<\Modules\Catalog\Database\Factories\ProductMediaFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
