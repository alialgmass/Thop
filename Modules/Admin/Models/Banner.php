<?php

namespace Modules\Admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Database\Factories\BannerFactory;
use Modules\Search\Models\FeaturedPlacement;

/**
 * A homepage banner for the separate marketplace client (Phase 9 · T6,
 * issue #35) — this repo has no homepage of its own; `GET /api/v1/banners`
 * is the only consumer.
 *
 * @property int $id
 * @property string $image_disk
 * @property string $image_path
 * @property string|null $link_url
 * @property int $position
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 * @property int $created_by
 */
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
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
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imageUrl(): string
    {
        return Storage::disk($this->image_disk)->url($this->image_path);
    }

    /**
     * `is_active` AND within its date window. Matches the shape of
     * {@see FeaturedPlacement::scopeActive()}.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
