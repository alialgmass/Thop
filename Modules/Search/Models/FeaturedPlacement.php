<?php

namespace Modules\Search\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Search\Database\Factories\FeaturedPlacementFactory;

/**
 * An admin-curated "featured" slot for one product or supplier, for a date
 * window, independent of the featurable's own subscription plan (US-SRC-10,
 * BR-SRC-01). `featurable_type` is the morph alias from
 * {@see FavoritableType} ('product' | 'supplier'),
 * not a raw class name — reused rather than re-declared.
 *
 * @property int $id
 * @property string $featurable_type
 * @property int $featurable_id
 * @property string $slot
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $created_by
 */
class FeaturedPlacement extends Model
{
    /** @use HasFactory<FeaturedPlacementFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function newFactory(): FeaturedPlacementFactory
    {
        return FeaturedPlacementFactory::new();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function featurable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Within its date window: started (or no start set) and not yet ended (or
     * no end set). An expired placement drops out automatically — no cron,
     * no cleanup job, just a query condition (Phase 9 · T5 acceptance
     * criterion "expired placements drop automatically").
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * The in-memory equivalent of {@see self::scopeActive()}, for an
     * already-loaded record where re-querying would be wasteful (e.g. one
     * row of a Filament table).
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();

        return ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gt($now));
    }
}
