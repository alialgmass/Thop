<?php

namespace Modules\Subscriptions\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schedule;
use Modules\Subscriptions\Console\ProcessSubscriptionPeriodEnds;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Policies\SubscriptionPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SubscriptionsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Subscriptions';

    protected string $nameLower = 'subscriptions';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'subscriptions');

        $this->commands([
            ProcessSubscriptionPeriodEnds::class,
        ]);

        // Enforce deferred downgrades/cancellations and expiry at period end
        // (BR-SUB-02, BR-SUB-03). Preregistered via the queue worker process.
        Schedule::command(ProcessSubscriptionPeriodEnds::class)->daily();

        Gate::policy(Subscription::class, SubscriptionPolicy::class);
    }
}
