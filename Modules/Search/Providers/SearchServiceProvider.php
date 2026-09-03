<?php

namespace Modules\Search\Providers;

use Illuminate\Routing\Router;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Search\Http\Middleware\ResolveOptionalUser;
use Modules\Search\Observers\BusinessAccountSearchIndexer;
use Modules\Search\Observers\ProductSearchIndexer;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SearchServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Search';

    protected string $nameLower = 'search';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'search');

        $this->app->make(Router::class)->aliasMiddleware('optional.sanctum', ResolveOptionalUser::class);

        // Keep the normalized `search_text` column in sync so both the MySQL
        // FULLTEXT path and the SQLite LIKE fallback compare normalized text.
        Product::observe(ProductSearchIndexer::class);
        BusinessAccount::observe(BusinessAccountSearchIndexer::class);
    }
}
