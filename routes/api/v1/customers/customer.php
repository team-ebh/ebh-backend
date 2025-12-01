<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:customer')
    ->controller(CustomerController::class)
    ->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::get('/app-state', 'appState')->name('app-state');
    });
