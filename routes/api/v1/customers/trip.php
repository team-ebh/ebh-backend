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
        Route::post('/', 'store')->name('store');
        Route::post('/{trip}/change-ride-type', 'changeRideType')->name('change-ride-type');
    });
