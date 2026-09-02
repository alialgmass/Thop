<?php

namespace Modules\Core\Providers;

use Illuminate\Routing\Router;
use Modules\Core\Http\Middleware\AcceptJsonMiddleware;
use Modules\Core\Http\Middleware\AppLanguage;
use Modules\Core\Http\Middleware\Authenticate;
use Modules\Core\Http\Middleware\CustomThrottleRequests;
use Modules\Core\Http\Middleware\TimezoneConfig;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CoreServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Core';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'core';

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'app');
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'enum');
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'exception');
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'exceptions');

        $this->registerMiddlewareAliases();
    }

    private function registerMiddlewareAliases(): void
    {
        $router = app(Router::class);

        $router->aliasMiddleware('accept.json', AcceptJsonMiddleware::class);
        $router->aliasMiddleware('api.language', AppLanguage::class);
        $router->aliasMiddleware('api.timezone', TimezoneConfig::class);
        $router->aliasMiddleware('throttle.phone', CustomThrottleRequests::class);
        $router->aliasMiddleware('auth.admin', Authenticate::class);
    }
}
