<?php

namespace Modules\Taxonomy\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class TaxonomyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Taxonomy';

    protected string $nameLower = 'taxonomy';

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

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'taxonomy');
    }
}
