<?php

use Illuminate\Support\Facades\Route;
use Modules\Businesses\Http\Controllers\BusinessController;

Route::prefix('v1/businesses')->name('businesses.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [BusinessController::class, 'store'])->name('store');
    Route::get('{business}', [BusinessController::class, 'show'])->name('show');
    Route::patch('{business}', [BusinessController::class, 'update'])->name('update');
});
