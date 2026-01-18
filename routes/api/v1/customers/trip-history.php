<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\HistoryTripController;
use Illuminate\Support\Facades\Route;

// Trip History endpoints
Route::name('trip-history.')
    ->prefix('trip-history')
    ->controller(HistoryTripController::class)
    ->middleware(['auth:customer', 'customer.active'])
    ->group(function () {
        Route::get('/', 'trips')->name('trips');
        Route::get('/{trip}/details', 'details')->name('details');
    });
