<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationFeedController;
use Modules\Notifications\Http\Controllers\NotificationPreferenceController;

Route::middleware('auth:sanctum')->prefix('v1')->name('notifications.')->group(function (): void {
    Route::prefix('notifications')->name('feed.')->group(function (): void {
        Route::get('/', [NotificationFeedController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationFeedController::class, 'unreadCount'])->name('unread-count');
        Route::post('/read-all', [NotificationFeedController::class, 'readAll'])->name('read-all');
        Route::post('/{notification}/read', [NotificationFeedController::class, 'read'])->name('read');
    });

    Route::prefix('notification-preferences')->name('preferences.')->group(function (): void {
        Route::get('/', [NotificationPreferenceController::class, 'index'])->name('index');
        Route::put('/', [NotificationPreferenceController::class, 'update'])->name('update');
        Route::put('/marketing', [NotificationPreferenceController::class, 'updateMarketing'])->name('marketing');
    });
});
