<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use App\Http\Controllers\Api\V1\TestSocketController;
use Illuminate\Support\Facades\Route;

// WebSocket test API endpoints (only available in non-risky environments)
if (! ApplicationEnvironmentEnum::isRiskyEnvironment()) {
    Route::get('connection-info', [TestSocketController::class, 'getConnectionInfo']);
    Route::post('send', [TestSocketController::class, 'sendMessage']);
}
