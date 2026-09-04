<?php

namespace Modules\Favorites\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Favorites\Models\Favorite;
use Modules\Favorites\Policies\FavoritePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class FavoritesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Favorites';

    protected string $nameLower = 'favorites';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'favorites');

        // Store short aliases ("product"/"supplier") in favoritable_type rather
        // than FQCNs. Non-enforcing so other polymorphic models keep FQCNs.
        Relation::morphMap(FavoritableType::morphMap());

        Gate::policy(Favorite::class, FavoritePolicy::class);
    }
}
