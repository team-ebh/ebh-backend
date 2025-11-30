<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\SettingsController;
use Illuminate\Support\Facades\Route;

Route::name('settings.')
    ->prefix('settings')
    ->group(function () {
        // Get rider settings
        Route::get('/', [SettingsController::class, 'index'])
            ->name('index');
    });
