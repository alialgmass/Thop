<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AccountTypeController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\MeController;
use Modules\Auth\Http\Controllers\OtpController;
use Modules\Auth\Http\Controllers\PasswordResetController;
use Modules\Auth\Http\Controllers\RegisterController;

Route::prefix('v1/auth')->name('auth.')->group(function () {
    Route::post('otp/request', [OtpController::class, 'request'])->name('otp.request');
    Route::post('otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', [LoginController::class, 'store'])->name('login');
    Route::post('password/reset', PasswordResetController::class)->name('password.reset');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', MeController::class)->name('me');
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::post('account-type', AccountTypeController::class)->name('account-type');
    });
});
