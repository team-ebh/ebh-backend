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
        Route::get('/{payment:payment_number}/download-receipt', 'downloadReceipt')->name('download-receipt');

        Route::middleware('auth:customer')->group(function () {
            Route::get('/check-pending', 'checkPendingPayment')->name('check-pending');
            Route::post('/link', 'getLink')->name('link');
            Route::get('/{payment:payment_number}/check-status', 'checkPaymentStatus')->name('check-status');
            Route::get('/{payment:payment_number}/receipt-link', 'getReceiptLink')->name('receipt-link');
        });
    });
