<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\EarningsController;
use Illuminate\Support\Facades\Route;

// Earnings endpoints
Route::name('earnings.')
    ->prefix('earnings')
    ->controller(EarningsController::class)
    ->middleware(['auth:rider', 'rider.enabled'])
    ->group(function () {
        // Filters
        Route::get('/filters', 'filters')->name('filters');

        // Report (summary statistics)
        Route::get('/report', 'report')->name('report');

        // Latest trips
        Route::get('/latest-trips', 'latestTrips')->name('latest-trips');
    });
