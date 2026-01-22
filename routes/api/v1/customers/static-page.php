<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::name('static-pages.')
    ->prefix('static-pages')
    ->group(function () {
        Route::get('/', [StaticPageController::class, 'index'])->name('index');
        Route::get('/{staticPage}', [StaticPageController::class, 'show'])->name('show');
    });
