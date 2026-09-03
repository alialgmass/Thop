<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quantity-based price break: at min_qty the unit price becomes unit_price
 * (US-SEL-06). No timestamps, mirroring SubscriptionEntitlement.
 *
 * @property int $id
 * @property int $product_id
 * @property int $min_qty
 * @property string $unit_price
 */
class ProductPriceTier extends Model
{
    /** @use HasFactory<\Modules\Catalog\Database\Factories\ProductPriceTierFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_qty' => 'integer',
            'unit_price' => 'decimal:2',
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
