<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\Http\Controllers\SubscriptionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('subscription-plans', [SubscriptionController::class, 'plans'])
        ->name('subscription-plans.index');

    Route::post('subscriptions', [SubscriptionController::class, 'store'])
        ->name('subscriptions.store');

    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])
        ->name('subscriptions.show');

    Route::get('subscriptions/{subscription}/usage', [SubscriptionController::class, 'usage'])
        ->name('subscriptions.usage');

    Route::patch('subscriptions/{subscription}', [SubscriptionController::class, 'update'])
        ->name('subscriptions.update');
});
