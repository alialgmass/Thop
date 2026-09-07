<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ConversationController;
use Modules\Chat\Http\Controllers\MessageController;
use Modules\Chat\Http\Controllers\MessageReportController;

Route::middleware('auth:sanctum')->prefix('v1')->name('chat.')->group(function (): void {
    // Idempotent open/create of the thread bound to an inquiry (US-CHT-01).
    Route::post('inquiries/{inquiry}/conversation', [ConversationController::class, 'store'])
        ->name('conversations.store');

    Route::prefix('conversations')->name('conversations.')->group(function (): void {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
        Route::post('/{conversation}/read', [ConversationController::class, 'read'])->name('read');

        Route::get('/{conversation}/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/{conversation}/messages/{message}/reports', [MessageReportController::class, 'store'])
            ->name('messages.reports.store');
    });
});
