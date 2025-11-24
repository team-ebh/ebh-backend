<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\TripController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:rider')
    ->name('trips.requests.')
    ->prefix('trips/requests')
    ->controller(TripController::class)
    ->group(function () {
        Route::get('', 'requests')->name('requests');
        Route::get('/active', 'activeTrip')->name('active');
        Route::post('/{tripRequest}/accept', 'accept')->name('requests.accept');
        Route::post('/{tripRequest}/decline', 'decline')->name('requests.decline');
        Route::post('/{tripRequest}/cancel', 'cancel')->name('requests.cancel');
        Route::post('/{tripRequest}/arrived', 'arrivedTripLocation')->name('requests.arrived');
        Route::post('/{tripRequest}/picked-up', 'pickUpPassenger')->name('requests.pickup');
        Route::post('/{tripRequest}/completed', 'completeTripLocation')->name('requests.complete');
    });
