<?php

use Illuminate\Support\Facades\Route;
use Modules\Verification\Http\Controllers\AdminDocumentDownloadController;

Route::get('verification-documents/{document}/download', AdminDocumentDownloadController::class)
    ->name('documents.download');
