<?php

use Illuminate\Support\Facades\Route;
use Modules\Taxonomy\Http\Controllers\TaxonomyController;

Route::prefix('v1/taxonomy')->name('taxonomy.')->group(function () {
    Route::get('governorates', [TaxonomyController::class, 'governorates'])->name('governorates');
    Route::get('fabric-types', [TaxonomyController::class, 'fabricTypes'])->name('fabric-types');
    Route::get('materials', [TaxonomyController::class, 'materials'])->name('materials');
    Route::get('colors', [TaxonomyController::class, 'colors'])->name('colors');
    Route::get('units', [TaxonomyController::class, 'units'])->name('units');
});
