<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use App\Exceptions\BaseException;
use App\Http\Middleware\AuthenticateBroadcasting;
use App\Http\Middleware\EnsureCustomerIsActive;
use App\Http\Middleware\EnsureRiderIsEnabled;
use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {
            $routeGroups = [
                'riders' => glob(base_path('routes/api/v*/riders/*.php')),
                'customers' => glob(base_path('routes/api/v*/customers/*.php')),
            ];

            // Add test routes only in non-risky environments
            if (! ApplicationEnvironmentEnum::isRiskyEnvironment()) {
                $routeGroups['test'] = glob(base_path('routes/api/v*/test/*.php'));
            }

            foreach ($routeGroups as $group => $files) {
                foreach ($files as $file) {
                    $parts = explode('/', $file);
                    $version = mb_strtolower($parts[count($parts) - 3]);

                    Route::prefix("{$version}/{$group}")
                        ->domain(config('app.domains.api'))
                        ->name("{$version}.{$group}.")
                        ->middleware([
                            'api',
                            'throttle:limiter',
                            LocalizationMiddleware::class,
                        ])
                        ->group($file);
                }
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('login');

        // Exclude broadcasting auth from CSRF verification (uses Bearer token)
        $middleware->validateCsrfTokens(except: [
            'broadcasting/auth',
        ]);

        // Add custom middleware for broadcasting authentication
        $middleware->alias([
            'auth.broadcasting' => AuthenticateBroadcasting::class,
            'customer.active' => EnsureCustomerIsActive::class,
            'rider.enabled' => EnsureRiderIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ThrottleRequestsException $exceptions) {
            return response()->json([
                'data' => null,
                'meta' => [
                    'message' => trans('exceptions.too_many_requests'),
                    'errors' => null,
                ],
            ], $exceptions->getStatusCode());
        });

        $exceptions->report(function (Throwable $exception) {
            $reportWhiteListStatusCode = [
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ];

            if ($exception instanceof BaseException && ! in_array(
                $exception->statusCode(),
                $reportWhiteListStatusCode,
                true
            )) {
                return false;
            }

            Integration::captureUnhandledException($exception);

            return true;
        });
    })
    ->create();
