<?php

namespace Modules\Businesses\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Businesses\Policies\BusinessPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BusinessesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Businesses';

    protected string $nameLower = 'businesses';

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

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'businesses');

        Gate::policy(BusinessAccount::class, BusinessPolicy::class);
    }
}
