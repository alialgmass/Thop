<?php

namespace Modules\Favorites\Policies;

use App\Models\User;
use Modules\Favorites\Models\Favorite;

/**
 * Favorites are private to their owner (§8 matrix: "CRUD (own)"). Removing
 * someone else's favorite is a 403 (US-SRC-08).
 */
class FavoritePolicy
{
    public function delete(User $user, Favorite $favorite): bool
    {
        return $favorite->user_id === $user->getKey();
    }
}
