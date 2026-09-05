<?php

namespace Modules\Inquiries\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Quotation;
use Modules\Inquiries\Models\Rfq;
use Modules\Inquiries\Policies\InquiryPolicy;
use Modules\Inquiries\Policies\QuotationPolicy;
use Modules\Inquiries\Policies\RfqPolicy;
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

        // Store the short alias ("inquiry") in reportable_type rather than
        // the FQCN. Non-enforcing, same pattern as Favorites' FavoritableType.
        Relation::morphMap(ReportableType::morphMap());

        Gate::policy(Inquiry::class, InquiryPolicy::class);
        Gate::policy(Rfq::class, RfqPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
    }
}
