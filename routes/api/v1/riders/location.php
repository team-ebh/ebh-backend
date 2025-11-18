<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\RiderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:rider')
    ->controller(RiderController::class)
    ->middleware(['auth:rider'])
    ->group(function () {
        Route::post('/location', 'updateLocation')->name('location.update');
    });
