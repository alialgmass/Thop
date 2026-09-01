<?php

namespace Modules\Verification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Database\Factories\VerificationRequestFactory;
use Modules\Verification\Enums\VerificationRequestStatus;

/**
 * @property int $id
 * @property int $business_account_id
 * @property VerificationRequestStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property Carbon|null $submitted_at
 */
class VerificationRequest extends Model
{
    /** @use HasFactory<VerificationRequestFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => VerificationRequestStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VerificationRequestStatus::class,
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): VerificationRequestFactory
    {
        return VerificationRequestFactory::new();
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === VerificationRequestStatus::Pending;
    }

    /**
     * @return BelongsTo<BusinessAccount, $this>
     */
    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(BusinessAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<VerificationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }
}
