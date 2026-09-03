<?php

use Illuminate\Support\Facades\Route;
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
