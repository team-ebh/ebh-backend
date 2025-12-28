<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\TripController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:rider', 'rider.enabled'])
    ->name('trips.requests.')
    ->prefix('trips/requests')
    ->controller(TripController::class)
    ->group(function () {
        Route::get('', 'requests')->name('requests');
        Route::get('/active', 'activeTrip')->name('active');
        Route::post('/{tripRequest}/accept', 'accept')->name('accept');
        Route::post('/{tripRequest}/decline', 'decline')->name('decline');
        Route::post('/{tripRequest}/cancel', 'cancel')->name('cancel');
        Route::post('/{tripRequest}/arrived', 'arrivedTripLocation')->name('arrived');
        Route::post('/{tripRequest}/picked-up', 'pickUpPassenger')->name('picked-up');
        Route::post('/{tripRequest}/completed', 'completeTripLocation')->name('completed');
        Route::get('/{tripRequest}/estimated-arrival-time', 'estimatedArrivalTime')->name('estimated_arrival_time');
    });
