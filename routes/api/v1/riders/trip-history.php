<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\HistoryTripController;
use Illuminate\Support\Facades\Route;

// Trip History endpoints
Route::name('trip-history.')
    ->prefix('trip-history')
    ->controller(HistoryTripController::class)
    ->group(function () {
        // Download receipt (public with signed URL)
        Route::get('/{trip}/download-receipt', 'downloadReceipt')->name('download-receipt');

        Route::middleware(['auth:rider', 'rider.enabled'])->group(function () {
            // Filters and statistics
            Route::get('/filters', 'filters')->name('filters');

            // Past trips
            Route::get('/', 'index')->name('index');
            Route::get('/{trip}', 'show')->name('show');

            // Receipt link
            Route::get('/{trip}/receipt-link', 'receiptLink')->name('receipt-link');
        });
    });
