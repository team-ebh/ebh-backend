<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\HistoryTripController;
use Illuminate\Support\Facades\Route;

// Trip History endpoints
Route::name('trip-history.')
    ->prefix('trip-history')
    ->controller(HistoryTripController::class)
    ->group(function () {
        // Download receipt (public with signed URL)
        Route::get('/{trip}/download-receipt', 'downloadReceipt')->name('download-receipt');

        Route::middleware(['auth:customer', 'customer.active'])->group(function () {
            // Upcoming trips
            Route::get('/upcoming', 'upcomingTrips')->name('upcoming');
            Route::get('/upcoming/{trip}', 'upcomingDetails')->name('upcoming.details');

            // Past trips
            Route::get('/past', 'pastTrips')->name('past');
            Route::get('/past/{trip}', 'pastDetails')->name('past.details');

            // Receipt link
            Route::get('/{trip}/receipt-link', 'receiptLink')->name('receipt-link');
        });
    });
