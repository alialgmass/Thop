<?php

namespace Modules\Inquiries\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Inquiries\Database\Factories\InquiryFactory;
use Modules\Inquiries\Enums\LeadStatus;

/**
 * A buyer's contact with a seller, optionally about one product (US-INQ-01).
 * This row IS the seller's Lead (BR-INQ-01) — {@see $lead_status} is the
 * only "Lead" concept, there is no separate leads table (§10.5).
 *
 * @property int $id
 * @property int $buyer_id
 * @property int $seller_business_id
 * @property int|null $product_id
 * @property string|null $message
 * @property LeadStatus $lead_status
 */
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'lead_status' => LeadStatus::New->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lead_status' => LeadStatus::class,
        ];
    }

    protected static function newFactory(): InquiryFactory
    {
        return InquiryFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<BusinessAccount, $this>
     */
    public function sellerBusiness(): BelongsTo
    {
        return $this->belongsTo(BusinessAccount::class, 'seller_business_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Inquiries directed at a given seller business (Lead Management screen,
     * US-ANL-03) — this is the "seller sees only their own leads" scope.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForSeller(Builder $query, int $businessAccountId): void
    {
        $query->where('seller_business_id', $businessAccountId);
    }

    /**
     * Inquiries a given user sent as a buyer.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForBuyer(Builder $query, int $userId): void
    {
        $query->where('buyer_id', $userId);
    }
}
