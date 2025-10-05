<?php

declare(strict_types=1);

use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.domains.api'))
    ->group(function () {
        Scramble::registerUiRoute(path: '/v1/docs', api: 'v1');
        Scramble::registerJsonSpecificationRoute(path: '/v1/docs.json', api: 'v1');
    });
