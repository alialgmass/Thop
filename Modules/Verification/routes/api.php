<?php

use Illuminate\Support\Facades\Route;
use Modules\Verification\Http\Controllers\AdminVerificationController;
use Modules\Verification\Http\Controllers\VerificationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v1/businesses/{business}')->name('verification.')->group(function () {
        Route::post('verification-documents', [VerificationController::class, 'uploadDocument'])->name('documents.store');
        Route::get('verification-documents/{document}', [VerificationController::class, 'download'])
            ->middleware('signed')
            ->name('documents.download');
        Route::post('verification-request', [VerificationController::class, 'submit'])->name('submit');
        Route::get('verification-status', [VerificationController::class, 'status'])->name('status');
    });

    Route::prefix('v1/admin/verification-requests')->name('admin.verification-requests.')->group(function () {
        Route::get('/', [AdminVerificationController::class, 'queue'])->name('index');
        Route::post('{verificationRequest}/approve', [AdminVerificationController::class, 'approve'])->name('approve');
        Route::post('{verificationRequest}/reject', [AdminVerificationController::class, 'reject'])->name('reject');
    });
});
