<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\RiderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:rider', 'rider.enabled'])
    ->controller(RiderController::class)
    ->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
        Route::post('/profile/image', 'updateProfileImage')->name('profile.image.update');
        Route::post('/location', 'updateLocation')->name('location.update');
        Route::get('/app-state', 'appState')->name('app-state');
        Route::put('/status', 'updateStatus')->name('status.update');
    });
