<?php

declare(strict_types=1);

use App\Exceptions\BaseException;
use App\Http\Middleware\ForceApiGuardMiddleware;
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
        health: '/up',
        then: function () {
            $files = glob(base_path('routes/api/v*/*.php'));

            foreach ($files as $file) {
                $exploded = explode('/', $file);

                $version = mb_strtolower($exploded[count($exploded) - 2]);

                Route::prefix($version)
                    ->domain(config('app.domains.api'))
                    ->name($version . '.')
                    ->middleware([
                        'api',
                        'throttle:limiter',
                        ForceApiGuardMiddleware::class,
                        LocalizationMiddleware::class,
                    ])
                    ->group($file);
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('login');
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
