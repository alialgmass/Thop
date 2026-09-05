<?php

namespace Modules\Inquiries\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Database\Factories\RfqFactory;
use Modules\Taxonomy\Models\Color;

/**
 * A structured request-for-quotation, always attached to an inquiry thread
 * (US-INQ-02).
 *
 * @property int $id
 * @property int $inquiry_id
 * @property int $product_id
 * @property int $quantity
 * @property int|null $color_id
 * @property Carbon $needed_by_date
 */
class Rfq extends Model
{
    /** @use HasFactory<RfqFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'needed_by_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    protected static function newFactory(): RfqFactory
    {
        return RfqFactory::new();
    }

    /**
     * @return BelongsTo<Inquiry, $this>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Color, $this>
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class)->latest();
    }

    /**
     * Below-MOQ is a warning, never a block (US-INQ-02 Implementation
     * Assumption — the SRS doesn't say whether sub-MOQ RFQs are disallowed).
     */
    public function isBelowMoq(): bool
    {
        return $this->product->moq !== null && $this->quantity < $this->product->moq;
    }

    /**
     * Delegates to the parent inquiry — the single place "buyer/seller/either"
     * is decided (RfqPolicy never re-derives it).
     */
    public function isBuyer(User $user): bool
    {
        return $this->inquiry->isBuyer($user);
    }

    public function isSeller(User $user): bool
    {
        return $this->inquiry->isSeller($user);
    }

    public function involvesUser(User $user): bool
    {
        return $this->inquiry->involvesUser($user);
    }
}
