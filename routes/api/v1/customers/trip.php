<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\TripController;
use Illuminate\Support\Facades\Route;

Route::name('trips.')
    ->prefix('trips')
    ->controller(TripController::class)
    ->group(function () {
        Route::get('/form-data', 'formData')->name('form-data');
    });
