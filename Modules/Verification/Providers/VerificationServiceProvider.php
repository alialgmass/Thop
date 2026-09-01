<?php

namespace Modules\Verification\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class VerificationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Verification';

    protected string $nameLower = 'verification';

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

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'verification');
    }
}
