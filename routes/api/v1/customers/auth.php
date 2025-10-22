<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\AuthController;
use Illuminate\Support\Facades\Route;

Route::name('auth.')
    ->prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/sign-up', 'signUp')->name('sign-up');
        Route::post('/sign-up/verify-otp', 'signUpVerifyOtp')->name('sign-up.verify-otp');
        Route::post('/sign-in', 'signIn')->name('sign-in');
        Route::post('/sign-in/verify-otp', 'signInVerifyOtp')->name('sign-in.verify-otp');
        Route::post('/sign-out', 'signOut')->middleware('auth:api')->name('sign-out');
    });
