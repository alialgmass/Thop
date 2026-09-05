<?php

use Illuminate\Support\Facades\Route;
use Modules\Inquiries\Http\Controllers\InquiryController;

Route::middleware('auth:sanctum')->prefix('v1/inquiries')->name('inquiries.')->group(function (): void {
    Route::get('/', [InquiryController::class, 'index'])->name('index');
    Route::post('/', [InquiryController::class, 'store'])->name('store');
    Route::get('/{inquiry}', [InquiryController::class, 'show'])->name('show');
    Route::patch('/{inquiry}', [InquiryController::class, 'update'])->name('update');
});
