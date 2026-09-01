<?php

namespace Modules\Verification\Providers;

use App\Http\Middleware\RedirectIfNotAdmin;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Verification';

    public function map(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->name('api.')
            ->group(module_path($this->name, '/routes/api.php'));

        // Session-authenticated document streaming for the Filament admin panel.
        Route::middleware(['web', 'auth', RedirectIfNotAdmin::class])
            ->prefix('admin')
            ->name('admin.verification.')
            ->group(module_path($this->name, '/routes/admin.php'));
    }
}
