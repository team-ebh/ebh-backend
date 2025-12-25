<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\TripController;
use Illuminate\Support\Facades\Route;

// Trip endpoints
Route::name('trips.')
    ->prefix('trips')
    ->controller(TripController::class)
    ->group(function () {
        Route::get('/form-data', 'formData')->name('form-data');

        Route::middleware(['auth:customer', 'customer.active'])
            ->group(function () {
                Route::get('/active', 'activeTrip')->name('active');
                Route::post('/', 'store')->name('store');
                Route::post('/{trip}/change-ride-type', 'changeRideType')->name('change-ride-type');
                Route::post('/{trip}/confirm', 'confirm')->name('confirm');
                Route::get('/{trip}/status', 'getTripStatus')->name('status');
                Route::post('/{trip}/cancel', 'cancel')->name('cancel');
                Route::get('/{trip}/rider-location', 'getRiderLocation')->name('rider-location');
                Route::get('/{trip}/estimated-arrival-time', 'estimatedArrivalTime')->name('estimated-arrival-time');
            });
    });
