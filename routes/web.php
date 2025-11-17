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
            Route::domain(config('app.domains.admin'))->get('/test-socket', function () {
                return view('test-socket');
            });

            Route::domain(config('app.domains.admin'))->get('/websocket-sender', function () {
                return view('websocket-sender');
            });

            Route::domain(config('app.domains.admin'))->get('/websocket-receiver', function () {
                return view('websocket-receiver');
            });

            Route::domain(config('app.domains.admin'))->get('/websocket-docs', function () {
                return view('websocket-docs');
            });

            // Broadcast Monitor Pages
            Route::domain(config('app.domains.admin'))->get('/broadcast-monitor', function () {
                return view('broadcast-monitor.index');
            });

            Route::domain(config('app.domains.admin'))->get('/broadcast-monitor/customer', function () {
                return view('broadcast-monitor.customer');
            });

            Route::domain(config('app.domains.admin'))->get('/broadcast-monitor/rider', function () {
                return view('broadcast-monitor.rider');
            });

            Route::domain(config('app.domains.admin'))->get('/broadcast-monitor/unified', function () {
                return view('broadcast-monitor.unified');
            });

            Route::domain(config('app.domains.admin'))->get('/broadcast-monitor/selector', function () {
                return view('broadcast-monitor.selector');
            });
        }
    });
