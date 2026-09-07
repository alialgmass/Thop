<?php

namespace Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Chat\Database\Factories\ConversationFactory;
use Modules\Chat\Policies\ConversationPolicy;
use Modules\Inquiries\Models\Inquiry;

/**
 * A messaging thread, one per inquiry (US-CHT-01). Participation and every
 * authorization decision delegate to the linked {@see Inquiry} — this model
 * defines no participation primitive of its own, so REST and the Pusher
 * channel-auth callback share one source (US-CHT-02).
 *
 * @property int $id
 * @property int $inquiry_id
 * @property int $buyer_id
 * @property int $seller_business_id
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }

    /**
     * @return BelongsTo<Inquiry, $this>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
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
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Whether the user may take part in this conversation — the single check
     * both {@see ConversationPolicy} and the broadcast
     * channel authorization run (US-CHT-02).
     */
    public function involvesUser(User $user): bool
    {
        return $this->inquiry->involvesUser($user);
    }

    /**
     * Conversations the given user takes part in — as the buyer, or as the
     * owner of the seller-side business. The "my messages" scope.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForParticipant(Builder $query, User $user): void
    {
        $businessId = $user->businessAccount?->getKey();

        $query->where(function (Builder $q) use ($user, $businessId): void {
            $q->where('buyer_id', $user->getKey());

            if ($businessId !== null) {
                $q->orWhere('seller_business_id', $businessId);
            }
        });
    }

    /**
     * Add the caller's own unread count as an `unread_count` attribute.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithUnreadCountFor(Builder $query, User $user): void
    {
        $query->withCount(['messages as unread_count' => fn (Builder $q) => $q
            ->where('sender_id', '!=', $user->getKey())
            ->whereNull('read_at')]);
    }

    /**
     * The participant who is not the given user — the recipient of a message
     * the user sends (used by Phase 8 notifications).
     */
    public function counterpartOf(User $user): ?User
    {
        if ($this->inquiry->isBuyer($user)) {
            return $this->sellerBusiness->owner;
        }

        return $this->buyer;
    }

    /**
     * Messages in this conversation the given user has not read — everything
     * they did not send that has no `read_at` yet (US-CHT-07).
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->getKey())
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark every message the given user received (did not send) as read
     * (US-CHT-07). Returns the number of messages affected.
     */
    public function markReadFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
