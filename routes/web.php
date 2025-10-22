<?php

declare(strict_types=1);

use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.domains.api'))
    ->group(function () {
        Scramble::registerUiRoute('docs/v1/riders', 'v1-riders');
        Scramble::registerJsonSpecificationRoute('docs/v1/riders.json', 'v1-riders');

        Scramble::registerUiRoute('docs/v1/users', 'v1-users');
        Scramble::registerJsonSpecificationRoute('docs/v1/users.json', 'v1-users');

        Route::redirect('/', 'docs/v1/riders');
    });
