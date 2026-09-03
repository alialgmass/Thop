<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Actions\CreateProduct;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Policies\ProductPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Catalog';

    protected string $nameLower = 'catalog';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'catalog');

        Gate::policy(Product::class, ProductPolicy::class);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(CreateProduct::class);
    }
}
