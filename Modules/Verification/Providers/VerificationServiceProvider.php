<?php

namespace Modules\Verification\Providers;

use Modules\Verification\Contracts\FileScanner;
use Modules\Verification\Support\NullFileScanner;
use Modules\Verification\Support\SignatureFileScanner;
use Nwidart\Modules\Support\ModuleServiceProvider;

class VerificationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Verification';

    protected string $nameLower = 'verification';

    /**
     * Concrete {@see FileScanner} per configured driver. Add a real ClamAV /
     * hosted-scanner adapter here as a new key; the binding stays config-driven
     * (SEC-NFR-05).
     *
     * @var array<string, class-string<FileScanner>>
     */
    private const SCANNERS = [
        'signature' => SignatureFileScanner::class,
        'null' => NullFileScanner::class,
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

        $this->app->singleton(FileScanner::class, function (): FileScanner {
            $driver = (string) config('verification.scanner', 'signature');

            $scanner = self::SCANNERS[$driver]
                ?? throw new \InvalidArgumentException("Unknown verification scanner [{$driver}].");

            return $this->app->make($scanner);
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'verification');
    }
}
