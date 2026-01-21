<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:customer', 'customer.active'])
    ->controller(CustomerController::class)
    ->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
        Route::post('/profile/image', 'updateProfileImage')->name('profile.image.update');
        Route::get('/app-state', 'appState')->name('app-state');
    });
