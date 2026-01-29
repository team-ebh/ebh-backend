<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\DeleteAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('delete-account')
    ->name('delete-account.')
    ->middleware(['auth:customer', 'customer.active'])
    ->controller(DeleteAccountController::class)
    ->group(function () {
        Route::post('/send-otp', 'sendOtp')->name('send-otp');
        Route::post('/verify-otp', 'verifyOtp')->name('verify-otp');
        Route::post('/confirm', 'confirmDeleteAccount')->name('confirm');
    });
