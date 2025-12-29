<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Custom broadcasting auth endpoint with Bearer token support (only on local/dev)
Route::domain(config('app.domains.admin'))
    ->post('/broadcasting/auth', function (Request $request) {
        // Debug logging
        \Log::info('Broadcasting auth request received', [
            'has_auth_header' => $request->hasHeader('Authorization'),
            'channel_name' => $request->input('channel_name'),
            'socket_id' => $request->input('socket_id'),
        ]);

        // Manual Bearer token authentication
        $token = $request->bearerToken();

        \Log::info('Token check', [
            'has_token' => (bool) $token,
            'token_preview' => $token ? substr($token, 0, 10) . '...' : null,
        ]);

        if (! $token) {
            \Log::error('No bearer token provided');
            abort(403, 'No bearer token');
        }

        // Find the token in database
        $personalAccessToken = Laravel\Sanctum\PersonalAccessToken::findToken($token);

        \Log::info('Token lookup', [
            'token_found' => (bool) $personalAccessToken,
        ]);

        if (! $personalAccessToken) {
            \Log::error('Invalid token');
            abort(403, 'Invalid token');
        }

        $user = $personalAccessToken->tokenable;

        \Log::info('User found', [
            'user_id' => $user->id,
            'user_type' => get_class($user),
        ]);

        // Set the authenticated user for broadcasting
        $request->setUserResolver(fn () => $user);

        // Authorize the channel
        try {
            $response = Broadcast::auth($request);

            \Log::info('Broadcasting auth successful', [
                'user_id' => $user->id,
                'channel' => $request->input('channel_name'),
            ]);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Broadcasting auth exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(403, 'Channel authorization failed: ' . $e->getMessage());
        }
    });

Route::domain(config('app.domains.api'))
    ->group(function () {
        Scramble::registerUiRoute('docs/v1/riders', 'v1-riders');
        Scramble::registerJsonSpecificationRoute('docs/v1/riders.json', 'v1-riders');

        Scramble::registerUiRoute('docs/v1/customers', 'v1-customers');
        Scramble::registerJsonSpecificationRoute('docs/v1/customers.json', 'v1-customers');

        Route::redirect('/', 'docs/v1/riders');

        // WebSocket test pages (only available in non-risky environments)
        if (! ApplicationEnvironmentEnum::isProduction()) {
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

            // Private Channel Test Pages (Authentication + WebSocket)
            Route::domain(config('app.domains.admin'))->get('/socket', function () {
                return view('broadcast-test.index');
            });

            Route::domain(config('app.domains.admin'))->get('/socket/rider', function () {
                return view('broadcast-test.rider');
            });

            Route::domain(config('app.domains.admin'))->get('/socket/customer', function () {
                return view('broadcast-test.customer');
            });
        }
    });
