<?php

namespace Modules\Businesses\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Auth\Enums\UserStatus;
use Modules\Businesses\Database\Factories\BusinessAccountFactory;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Catalog\Models\Product;
use Modules\Core\Support\Traits\HasCreatedByColumn;
use Modules\Core\Support\Traits\HasUpdatedByColumn;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Services\EntitlementService;
use Modules\Taxonomy\Models\Governorate;
use Modules\Verification\Models\VerificationRequest;

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
     * The contact channels a given viewer may see (US-INQ-05, Open Decision #4).
     * The owner and admins always see them; any other viewer (a browsing buyer)
     * sees them only when the seller's active plan grants the
     * `contact_info_visible` entitlement — resolved server-side through the
     * single {@see EntitlementService} gate, never a client hint. Returns null
     * when the channels must be withheld. The one place this rule lives.
     *
     * @return array<int, array<string, string>>|null
     */
    public function contactChannelsVisibleTo(?User $viewer): ?array
    {
        $visible = $viewer?->can('view', $this)
            || app(EntitlementService::class)->can($this, 'contact_info_visible');

        return $visible ? ($this->contact_channels ?? []) : null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Business accounts whose owning user account is not suspended — the shared
     * "this supplier is reachable" gate for buyer-facing search, comparison and
     * catalog visibility.
     *
     * @param  Builder<static>  $query
     */
    public function scopeActiveAccount(Builder $query): void
    {
        $query->whereHas('owner', fn (Builder $owner) => $owner->where('status', '!=', UserStatus::Suspended->value));
    }

    /**
     * Business accounts whose catalog is currently sellable with respect to the
     * subscription lifecycle (BR-SUB-03): a business whose subscription has
     * *lapsed* (Restricted / Expired / Cancelled, or the paid period elapsed)
     * has its products hidden until it renews. Products are never deleted — a
     * pure visibility gate.
     *
     * DELIBERATE SCOPE DECISION (issue #17): a business that has *never*
     * subscribed keeps its current visibility. BR-SUB-03 / US-SUB-08 speak only
     * of *expiry* ("on expiry, products are hidden"), and pre-launch sellers
     * routinely publish before choosing a plan. Hard-gating never-subscribed
     * catalogs is a product call — tracked with Open Decision #5 (issue #26).
     * Flip the `whereDoesntHave` branch to close it.
     *
     * @param  Builder<static>  $query
     */
    public function scopeSubscriptionAllowsCatalog(Builder $query): void
    {
        $query->where(
            fn (Builder $q) => $q
                ->whereDoesntHave('subscriptions')
                ->orWhereHas('subscriptions', fn (Builder $sub) => $sub->currentlyActive())
        );
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
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
