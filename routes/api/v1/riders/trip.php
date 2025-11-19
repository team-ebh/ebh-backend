<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\TripController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:rider')
    ->name('trips.')
    ->prefix('trips')
    ->controller(TripController::class)
    ->middleware(['auth:rider'])
    ->group(function () {
        Route::get('/requests', 'requests')->name('requests');
        Route::get('/requests/active', 'activeTrip')->name('active');
        Route::post('/requests/{tripRequest}/accept', 'accept')->name('requests.accept');
        Route::post('/requests/{tripRequest}/decline', 'decline')->name('requests.decline');
        Route::post('/requests/{tripRequest}/cancel', 'cancel')->name('requests.cancel');
    });
