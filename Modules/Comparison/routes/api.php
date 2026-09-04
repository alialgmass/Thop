<?php

use Illuminate\Support\Facades\Route;
use Modules\Comparison\Http\Controllers\ComparisonController;

Route::middleware('auth:sanctum')->prefix('v1')->name('comparison.')->group(function (): void {
    Route::get('/compare', [ComparisonController::class, 'show'])->name('show');
});
