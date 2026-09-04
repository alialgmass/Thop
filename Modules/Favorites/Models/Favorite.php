<?php

namespace Modules\Favorites\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Favorites\Database\Factories\FavoriteFactory;
use Modules\Favorites\Providers\FavoritesServiceProvider;

/**
 * One saved target (a product or a supplier) for one user (US-SRC-08).
 * `favoritable_type` stores the short morph alias (`product` / `supplier`)
 * registered in {@see FavoritesServiceProvider}.
 *
 * @property int $id
 * @property int $user_id
 * @property string $favoritable_type
 * @property int $favoritable_id
 */
class Favorite extends Model
{
    /** @use HasFactory<FavoriteFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected static function newFactory(): FavoriteFactory
    {
        return FavoriteFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
