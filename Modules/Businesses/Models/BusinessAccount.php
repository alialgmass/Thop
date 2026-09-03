<?php

namespace Modules\Businesses\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Businesses\Database\Factories\BusinessAccountFactory;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Core\Support\Traits\HasCreatedByColumn;
use Modules\Core\Support\Traits\HasUpdatedByColumn;
use Modules\Subscriptions\Models\Subscription;
use Modules\Taxonomy\Models\Governorate;
use Modules\Verification\Models\VerificationRequest;
use Modules\Catalog\Models\Product;

/**
 * @property int $id
 * @property int $user_id
 * @property string $company_name
 * @property string $activity
 * @property int $governorate_id
 * @property string $address
 * @property string $contact_person
 * @property array<int, array<string, string>>|null $contact_channels
 * @property VerificationStatus $verification_status
 * @property bool $onboarded_by_admin
 */
class BusinessAccount extends Model
{
    /** @use HasFactory<BusinessAccountFactory> */
    use HasCreatedByColumn;

    use HasFactory;
    use HasUpdatedByColumn;

    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_status' => VerificationStatus::Unverified->value,
        'onboarded_by_admin' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_channels' => 'array',
            'verification_status' => VerificationStatus::class,
            'onboarded_by_admin' => 'boolean',
        ];
    }

    protected static function newFactory(): BusinessAccountFactory
    {
        return BusinessAccountFactory::new();
    }

    /**
     * Whether the "Verified" badge should render — resolved server-side from
     * {@see $verification_status}, never from client input (US-ACC-05, SEC-NFR-04).
     */
    public function isVerified(): bool
    {
        return $this->verification_status->isVerified();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Governorate, $this>
     */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /**
     * @return HasMany<VerificationRequest, $this>
     */
    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * @return HasOne<VerificationRequest, $this>
     */
    public function latestVerificationRequest(): HasOne
    {
        return $this->hasOne(VerificationRequest::class)->latestOfMany();
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
