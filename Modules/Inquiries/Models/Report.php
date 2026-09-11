<?php

namespace Modules\Inquiries\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Database\Factories\ReportFactory;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Enums\ReportStatus;

/**
 * Either party flagging an inquiry or chat message as abusive (US-INQ-09,
 * US-CHT-09). Durable record for the admin dispute queue (US-ADM-08, Phase
 * 9 · T9), which also owns the resolution workflow columns.
 *
 * @property int $id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property int $reporter_id
 * @property string $reason
 * @property ReportStatus $status
 * @property int|null $resolved_by
 * @property string|null $resolution_note
 * @property Carbon|null $resolved_at
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpenFirst(Builder $query): Builder
    {
        // Portable across MySQL and SQLite (this repo's dual test driver):
        // a boolean expression evaluates to 0/1 on both, so ordering by it
        // ascending puts an open report (0, i.e. "not true") first.
        return $query
            ->orderByRaw('(status != ?)', [ReportStatus::Open->value])
            ->orderByDesc('created_at');
    }

    /**
     * Eager-loads everything {@see self::parties()} and the reporter column
     * need, in one query per relation regardless of how many reports are in
     * the page — the admin queue (REST + Filament) both list many reports at
     * once, so this must not be optional. `MorphTo::morphWith()` is the
     * polymorphic-eager-load API for "different nested path per type".
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithPartiesEagerLoaded(Builder $query): Builder
    {
        return $query->with([
            'reporter',
            'reportable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Inquiry::class => ['buyer', 'sellerBusiness'],
                Message::class => ['conversation.inquiry.buyer', 'conversation.inquiry.sellerBusiness'],
            ]),
        ]);
    }

    /**
     * The buyer and seller business on the reported inquiry — resolved
     * whether the report is against the inquiry itself or a chat message on
     * it, since a message's conversation always belongs to exactly one
     * inquiry. The single place this dispatch lives, alongside
     * {@see ReportableType}. Callers driving a list (not a lone record)
     * should apply {@see self::scopeWithPartiesEagerLoaded()} first — this
     * method itself just walks whatever relations are already loaded (or
     * lazy-loads them for a single record, e.g. the Filament view page).
     *
     * @return array{buyer: User, sellerBusiness: BusinessAccount}
     */
    public function parties(): array
    {
        $inquiry = $this->reportable instanceof Message
            ? $this->reportable->conversation->inquiry
            : $this->reportable;

        return [
            'buyer' => $inquiry->buyer,
            'sellerBusiness' => $inquiry->sellerBusiness,
        ];
    }
}
