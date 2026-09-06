<?php

namespace Modules\Auth\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Support\CaptureOtpSender;
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
        'capture' => CaptureOtpSender::class,
    ];

    /**
     * Drivers that may only run in local development or the test suite — never
     * staging or production, where a real code would be cached in the clear.
     *
     * @var string[]
     */
    private const LOCAL_ONLY_DRIVERS = ['capture'];

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

            if (in_array($driver, self::LOCAL_ONLY_DRIVERS, true) && ! $this->app->environment('local', 'testing')) {
                throw new \RuntimeException("OTP driver [{$driver}] may only be used locally, not in [{$this->app->environment()}].");
            }

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
