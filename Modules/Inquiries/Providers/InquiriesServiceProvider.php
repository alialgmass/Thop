<?php

namespace Modules\Inquiries\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Policies\InquiryPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class InquiriesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Inquiries';

    protected string $nameLower = 'inquiries';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'inquiries');

        Gate::policy(Inquiry::class, InquiryPolicy::class);
    }
}
