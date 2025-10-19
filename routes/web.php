<?php

declare(strict_types=1);

use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.domains.api'))
    ->group(function () {
        Scramble::registerUiRoute('docs/v1/riders', 'v1-riders');
        Scramble::registerJsonSpecificationRoute('docs/v1/riders.json', 'v1-riders');

        Scramble::registerUiRoute('docs/v1/customers', 'v1-customers');
        Scramble::registerJsonSpecificationRoute('docs/v1/customers.json', 'v1-customers');

        Route::redirect('/', 'docs/v1/riders');
    });
