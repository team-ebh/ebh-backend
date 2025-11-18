<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\Trip\TripController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:rider')
    ->name('trips.')
    ->prefix('trips')
    ->middleware(['auth:rider'])
    ->group(function () {
        // Get available trip requests
        Route::get('/requests', [TripController::class, 'requests'])
            ->name('requests');

        // Get active trip
        Route::get('/requests/active', [TripController::class, 'activeTrip'])
            ->name('active');

        // Accept trip request
        Route::post('/requests/{tripRequest}/accept', [TripController::class, 'accept'])
            ->name('requests.accept');

        // Decline trip request
        Route::post('/requests/{tripRequest}/decline', [TripController::class, 'decline'])
            ->name('requests.decline');

        // Cancel accepted trip
        Route::post('/requests/{tripRequest}/cancel', [TripController::class, 'cancel'])
            ->name('requests.cancel');
    });
