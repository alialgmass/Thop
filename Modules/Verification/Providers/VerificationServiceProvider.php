<?php

namespace Modules\Verification\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Verification\Models\VerificationDocument;
use Modules\Verification\Models\VerificationRequest;
use Modules\Verification\Policies\VerificationPolicy;
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

        Gate::policy(VerificationRequest::class, VerificationPolicy::class);
        Gate::policy(VerificationDocument::class, VerificationPolicy::class);
    }
}
