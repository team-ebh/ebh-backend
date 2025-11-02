<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::name('v1.customers.')
    ->prefix('v1/customers')
    ->group(function () {
        require __DIR__ . '/api/v1/customers/auth.php';
        require __DIR__ . '/api/v1/customers/trip.php';
    });
