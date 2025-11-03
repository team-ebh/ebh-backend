<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\TestMessageSent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Testing
 */
class TestSocketController extends Controller
{
    /**
     * Send a test message via websocket
     *
     * @unauthenticated
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $message = $request->input('message', 'Hello from Laravel!');
        $sender = $request->input('sender', 'System');

        event(new TestMessageSent($message, $sender));

        return response()->json([
            'success' => true,
            'message' => 'Message broadcasted successfully',
            'data' => [
                'message' => $message,
                'sender' => $sender,
                'channel' => 'test-channel',
                'event' => 'message.sent',
            ],
        ])->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Language, Authorization');
    }

    /**
     * Get WebSocket connection information
     *
     * @unauthenticated
     */
    public function getConnectionInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'connection' => [
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'app_key' => config('broadcasting.connections.reverb.key'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                'channel' => 'test-channel',
                'event' => 'message.sent',
            ],
            'instructions' => [
                '1. Start Reverb server: php artisan reverb:start',
                '2. Connect to WebSocket using the connection info above',
                '3. Subscribe to channel: test-channel',
                '4. Listen for event: message.sent',
                '5. Send a message using POST /v1/test/send',
            ],
        ]);
    }
}
