<?php

use Illuminate\Support\Facades\Route;
use Modules\Inquiries\Http\Controllers\InquiryController;
use Modules\Inquiries\Http\Controllers\QuotationController;
use Modules\Inquiries\Http\Controllers\RfqController;

Route::middleware('auth:sanctum')->prefix('v1')->name('inquiries.')->group(function (): void {
    Route::prefix('inquiries')->group(function (): void {
        Route::get('/', [InquiryController::class, 'index'])->name('index');
        Route::post('/', [InquiryController::class, 'store'])->name('store');
        Route::get('/{inquiry}', [InquiryController::class, 'show'])->name('show');
        Route::patch('/{inquiry}', [InquiryController::class, 'update'])->name('update');
        Route::post('/{inquiry}/rfqs', [RfqController::class, 'store'])->name('rfqs.store');
    });

    Route::get('rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');
    Route::post('rfqs/{rfq}/quotations', [QuotationController::class, 'store'])->name('rfqs.quotations.store');
});
