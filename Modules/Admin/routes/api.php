<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AuditLogController;

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
    });
