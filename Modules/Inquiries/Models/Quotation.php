<?php

namespace Modules\Inquiries\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Inquiries\Database\Factories\QuotationFactory;

/**
 * The seller's time-bound reply to an RFQ (US-INQ-03). An expired
 * {@see $valid_until} makes the offer read as expired, not current pricing —
 * that's a presentation concern ({@see isExpired()}), not a status column.
 *
 * @property int $id
 * @property int $rfq_id
 * @property string $price
 * @property string|null $availability_note
 * @property Carbon $valid_until
 */
class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'valid_until' => 'datetime',
        ];
    }

    protected static function newFactory(): QuotationFactory
    {
        return QuotationFactory::new();
    }

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast();
    }

    /**
     * Delegates through the RFQ to its inquiry — hides that two-hop walk
     * from QuotationPolicy.
     */
    public function isSeller(User $user): bool
    {
        return $this->rfq->isSeller($user);
    }

    public function involvesUser(User $user): bool
    {
        return $this->rfq->involvesUser($user);
    }
}
