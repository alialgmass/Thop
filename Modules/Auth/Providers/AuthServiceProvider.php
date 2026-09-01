<?php

namespace Modules\Auth\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Support\FakeOtpSender;
use Modules\Auth\Support\LogOtpSender;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(OtpSender::class, function (): OtpSender {
            return $this->app->environment('testing')
                ? new FakeOtpSender
                : new LogOtpSender;
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'auth');

        JsonResource::withoutWrapping();
    }
}
