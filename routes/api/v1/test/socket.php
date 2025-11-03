<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use App\Http\Controllers\Api\V1\TestSocketController;
use Illuminate\Support\Facades\Route;

// WebSocket test API endpoints (only available in non-risky environments)
if (!ApplicationEnvironmentEnum::isRiskyEnvironment()) {
    Route::get('connection-info', [TestSocketController::class, 'getConnectionInfo']);
    Route::post('send', [TestSocketController::class, 'sendMessage']);

    // Handle CORS preflight request
    Route::options('send', function () {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Language, Authorization')
            ->header('Access-Control-Max-Age', '86400');
    });
}
