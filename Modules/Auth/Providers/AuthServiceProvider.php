<?php

namespace Modules\Auth\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Support\LogOtpSender;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Concrete {@see OtpSender} per configured driver. Add SMS-provider
     * adapters here as new keys; the binding stays config-driven.
     *
     * @var array<string, class-string<OtpSender>>
     */
    private const OTP_DRIVERS = [
        'log' => LogOtpSender::class,
    ];

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
            $driver = (string) config('auth.otp.driver', 'log');

            $sender = self::OTP_DRIVERS[$driver]
                ?? throw new \InvalidArgumentException("Unknown OTP driver [{$driver}].");

            return $this->app->make($sender);
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'auth');

        JsonResource::withoutWrapping();
    }
}
