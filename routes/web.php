<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.domains.api'))
    ->group(function () {
        Scramble::registerUiRoute('docs/v1/riders', 'v1-riders');
        Scramble::registerJsonSpecificationRoute('docs/v1/riders.json', 'v1-riders');

        Scramble::registerUiRoute('docs/v1/customers', 'v1-customers');
        Scramble::registerJsonSpecificationRoute('docs/v1/customers.json', 'v1-customers');

        Route::redirect('/', 'docs/v1/riders');

        // WebSocket test pages (only available in non-risky environments)
        if (! ApplicationEnvironmentEnum::isRiskyEnvironment()) {
            Route::get('/test-socket', function () {
                return view('test-socket');
            });

            Route::get('/websocket-sender', function () {
                return view('websocket-sender');
            });

            Route::get('/websocket-receiver', function () {
                return view('websocket-receiver');
            });

            Route::get('/websocket-docs', function () {
                return view('websocket-docs');
            });
        }
    });
