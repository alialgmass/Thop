<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AdminProductReviewController;
use Modules\Catalog\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Seller catalog (US-SEL-01..08). Public product search owns `GET /products`
    // and `GET /products/{id}` (Search module, §11.1); the seller's own listing
    // — all statuses — lives under `/products/mine`.
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/mine', [ProductController::class, 'index'])->name('mine');
        Route::get('/mine/{product}', [ProductController::class, 'show'])->name('mine.show');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::patch('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
        Route::patch('/{product}/status', [ProductController::class, 'updateStatus'])->name('status');
    });

    // Admin review queue (US-SEL-11, BR-ADM-01) — authorization via ProductPolicy
    Route::prefix('admin/products')->name('admin.products.')->group(function () {
        Route::get('/', [AdminProductReviewController::class, 'queue'])->name('index');
        Route::post('/{product}/approve', [AdminProductReviewController::class, 'approve'])->name('approve');
        Route::post('/{product}/reject', [AdminProductReviewController::class, 'reject'])->name('reject');
        Route::post('/{product}/hide', [AdminProductReviewController::class, 'hide'])->name('hide');
    });
});
