<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\ProfileController;
use App\Http\Controllers\Api\V1\Rider\RiderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:rider', 'rider.enabled'])
    ->group(function () {
        // Profile routes
        Route::controller(ProfileController::class)->group(function () {
            Route::get('/profile', 'show')->name('profile');
            Route::put('/profile', 'update')->name('profile.update');
            Route::post('/profile/image', 'store')->name('profile.image.update');
        });

        // Other rider routes
        Route::controller(RiderController::class)->group(function () {
            Route::post('/location', 'updateLocation')->name('location.update');
            Route::get('/app-state', 'appState')->name('app-state');
            Route::put('/status', 'updateStatus')->name('status.update');
        });
    });
