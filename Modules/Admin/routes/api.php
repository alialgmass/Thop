<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminAccountController;
use Modules\Admin\Http\Controllers\AuditLogController;
use Modules\Admin\Http\Controllers\BannerController;
use Modules\Admin\Http\Controllers\LiquidityDashboardController;

/**
 * Public — the separate marketplace client's homepage (Phase 9 · T6). No
 * authentication; this repo has no homepage of its own to render it on.
 * Management is Filament-only (issue #35 asks for no admin REST surface
 * here, unlike the other Phase 9 tickets).
 */
Route::get('v1/banners', [BannerController::class, 'index'])->name('banners.index');

/**
 * Back-office REST surface (spec §11, "admin"). Every route here is gated by
 * `auth:sanctum` + the `admin` role middleware — the same gate the Filament
 * panel enforces.
 */
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('v1/admin')
    ->name('admin.')
    ->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('dashboard/liquidity', [LiquidityDashboardController::class, 'index'])->name('dashboard.liquidity');

        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::post('{account}/suspend', [AdminAccountController::class, 'suspend'])->name('suspend');
            Route::post('{account}/reactivate', [AdminAccountController::class, 'reactivate'])->name('reactivate');
        });
    });
