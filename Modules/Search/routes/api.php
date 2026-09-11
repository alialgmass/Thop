<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\AdminFeaturedPlacementController;
use Modules\Search\Http\Controllers\ProductSearchController;
use Modules\Search\Http\Controllers\SupplierSearchController;

/**
 * Public buyer-facing discovery (US-SRC-01..07). No auth — the marketplace is
 * browsable before sign-in. The canonical `/products` path is search (§11.1);
 * the authenticated seller self-listing lives at `/products/mine` (Catalog).
 */
Route::middleware('optional.sanctum')->prefix('v1')->name('search.')->group(function (): void {
    Route::get('/products', [ProductSearchController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductSearchController::class, 'show'])
        ->whereNumber('product')
        ->name('products.show');

    Route::get('/businesses', [SupplierSearchController::class, 'index'])->name('businesses.index');
    Route::get('/businesses/{business}/catalog', [ProductSearchController::class, 'supplierCatalog'])
        ->whereNumber('business')
        ->name('businesses.catalog');
});

// Admin curation (US-SRC-10, BR-SRC-01, Phase 9 · T5).
Route::middleware(['auth:sanctum', 'admin'])->prefix('v1/admin/featured')->name('admin.featured.')->group(function (): void {
    Route::get('/', [AdminFeaturedPlacementController::class, 'index'])->name('index');
    Route::post('/', [AdminFeaturedPlacementController::class, 'store'])->name('store');
    Route::delete('{placement}', [AdminFeaturedPlacementController::class, 'destroy'])->name('destroy');
});
