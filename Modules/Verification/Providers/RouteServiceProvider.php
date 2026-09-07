<?php

namespace Modules\Verification\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureUserIsAdmin;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Verification';

    public function map(): void
    {
        Route::middleware(['api', 'api.language'])
            ->prefix('api')
            ->name('api.')
            ->group(module_path($this->name, '/routes/api.php'));

        // Session-authenticated document streaming for the Filament admin panel.
        Route::middleware(['web', 'auth', EnsureUserIsAdmin::class])
            ->prefix('admin')
            ->name('admin.verification.')
            ->group(module_path($this->name, '/routes/admin.php'));
    }
}
