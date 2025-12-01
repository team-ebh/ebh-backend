<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\RiderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:rider')
    ->controller(RiderController::class)
    ->group(function () {
        Route::post('/location', 'updateLocation')->name('location.update');
        Route::get('/app-state', 'appState')->name('app-state');
    });
