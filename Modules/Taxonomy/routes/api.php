<?php

use Illuminate\Support\Facades\Route;
use Modules\Taxonomy\Http\Controllers\AdminTaxonomyController;
use Modules\Taxonomy\Http\Controllers\TaxonomyController;

Route::prefix('v1/taxonomy')->name('taxonomy.')->group(function () {
    Route::get('governorates', [TaxonomyController::class, 'governorates'])->name('governorates');
    Route::get('fabric-types', [TaxonomyController::class, 'fabricTypes'])->name('fabric-types');
    Route::get('materials', [TaxonomyController::class, 'materials'])->name('materials');
    Route::get('colors', [TaxonomyController::class, 'colors'])->name('colors');
    Route::get('units', [TaxonomyController::class, 'units'])->name('units');
});

// Admin CRUD over the same reference lists (no delete — deactivate via
// update). {type} binds to ManagedTaxonomyType; an unrecognized segment 404s
// automatically via Laravel's backed-enum route binding.
Route::middleware(['auth:sanctum', 'admin'])->prefix('v1/admin/taxonomy')->name('admin.taxonomy.')->group(function () {
    Route::get('{type}', [AdminTaxonomyController::class, 'index'])->name('index');
    Route::post('{type}', [AdminTaxonomyController::class, 'store'])->name('store');
    Route::patch('{type}/{term}', [AdminTaxonomyController::class, 'update'])->name('update');
});
