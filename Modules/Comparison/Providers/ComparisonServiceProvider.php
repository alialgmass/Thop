<?php

namespace Modules\Comparison\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ComparisonServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Comparison';

    protected string $nameLower = 'comparison';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'comparison');
    }
}
