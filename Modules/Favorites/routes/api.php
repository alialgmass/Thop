<?php

use Illuminate\Support\Facades\Route;
use Modules\Favorites\Http\Controllers\FavoriteController;

Route::middleware('auth:sanctum')->prefix('v1/favorites')->name('favorites.')->group(function (): void {
    Route::get('/', [FavoriteController::class, 'index'])->name('index');
    Route::post('/', [FavoriteController::class, 'store'])->name('store');
    Route::delete('/{favorite}', [FavoriteController::class, 'destroy'])->name('destroy');
});
