<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\CustomerController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:customer', 'customer.active'])
    ->group(function () {
        // Profile routes
        Route::controller(ProfileController::class)->group(function () {
            Route::get('/profile', 'show')->name('profile');
            Route::put('/profile', 'update')->name('profile.update');
            Route::post('/profile/image', 'store')->name('profile.image.update');
        });

        // Other customer routes
        Route::controller(CustomerController::class)->group(function () {
            Route::get('/app-state', 'appState')->name('app-state');
        });
    });
