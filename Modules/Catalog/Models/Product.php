<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Database\Factories\ProductFactory;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Support\Traits\HasCreatedByColumn;
use Modules\Core\Support\Traits\HasUpdatedByColumn;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;

/**
 * A fabric product in a seller's digital catalog (US-SEL-01..11).
 *
 * @property int $id
 * @property int $business_account_id
 * @property int $fabric_type_id
 * @property int $material_id
 * @property int $governorate_id
 * @property string $name_ar
 * @property string|null $name_en
 * @property string|null $description
 * @property int|null $width_cm
 * @property int|null $weight_gsm
 * @property string|null $price
 * @property bool $price_on_contact
 * @property string $currency
 * @property string $unit
 * @property int|null $moq
 * @property int $quantity_available
 * @property ProductStatus $status
 * @property string|null $rejection_reason
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasCreatedByColumn;

    use HasFactory;
    use HasUpdatedByColumn;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ProductStatus::Draft->value,
        'price_on_contact' => false,
        'currency' => 'EGP',
        'unit' => 'per_meter',
        'quantity_available' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_on_contact' => 'boolean',
            'status' => ProductStatus::class,
            'moq' => 'integer',
            'quantity_available' => 'integer',
            'width_cm' => 'integer',
            'weight_gsm' => 'integer',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * @return BelongsTo<BusinessAccount, $this>
     */
    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(BusinessAccount::class);
    }

    /**
     * @return BelongsTo<FabricType, $this>
     */
    public function fabricType(): BelongsTo
    {
        return $this->belongsTo(FabricType::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<Governorate, $this>
     */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /**
     * @return BelongsToMany<Color, $this>
     */
    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'product_colors');
    }

    /**
     * @return HasMany<ProductPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class)->orderBy('min_qty');
    }

    /**
     * @return HasMany<ProductMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    /**
     * Whether the product has at least one attached image (US-SEL-03 gate).
     */
    public function hasImage(): bool
    {
        return $this->media()->where('type', 'image')->exists();
    }

    /**
     * BR-SEL-03: exactly one of {price, price_on_contact}. The request rules
     * enforce this; this is a hard guard used by {@see PublishProduct} as
     * defense in depth. Throws a 422 business-rule error when the rule breaks.
     */
    public function ensureValidPricing(): void
    {
        $hasPrice = $this->price !== null && $this->price !== '';
        $hasContact = (bool) $this->price_on_contact;

        if ($hasPrice === $hasContact) {
            throw ExceptionResponse::instance(__('catalog::messages.invalid_pricing'), 422)
                ->setCustomBody(['price' => [__('catalog::messages.invalid_pricing')]]);
        }
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublishedVisible(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    /**
     * The single definition of "a buyer may see this product" (BR-SRC-02):
     * published, not soft-deleted, and owned by a business whose account is
     * not suspended. Both global search and the per-supplier catalog use this.
     *
     * @param  Builder<static>  $query
     */
    public function scopeBuyerVisible(Builder $query): void
    {
        $query->where('status', ProductStatus::Published)
            ->whereHas('businessAccount', function ($business): void {
                $business->whereHas('owner', function ($owner): void {
                    $owner->where('status', '!=', 'suspended');
                });
            });
    }
}
