<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\PaymentController;
use Illuminate\Support\Facades\Route;

Route::name('payments.')
    ->prefix('payments')
    ->controller(PaymentController::class)
    ->group(function () {
        Route::any('/callback', 'processCallback')->name('callback');
        Route::any('/webhook', 'processWebhook')->name('webhook');

        Route::middleware('auth:customer')->group(function () {
            Route::post('/link', 'getLink')->name('link');
        });
    });
