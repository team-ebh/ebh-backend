<?php

declare(strict_types=1);

/**
 * Broadcasting Setup Diagnostic Test
 *
 * Run this script to diagnose broadcasting authentication issues:
 * php test_broadcasting_setup.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=========================================\n";
echo "Broadcasting Setup Diagnostic Test\n";
echo "=========================================\n\n";

// 1. Check environment
echo "1. Environment Check:\n";
echo '   APP_ENV: ' . config('app.env') . "\n";
echo '   Is Risky: ' . (\App\Enums\ApplicationEnvironmentEnum::isRiskyEnvironment() ? 'YES' : 'NO') . "\n";
echo '   Routes should exist: ' . (! \App\Enums\ApplicationEnvironmentEnum::isRiskyEnvironment() ? 'YES' : 'NO') . "\n";
echo "\n";

// 2. Check Reverb config
echo "2. Reverb Configuration:\n";
echo '   Key: ' . config('broadcasting.connections.reverb.key') . "\n";
echo '   Host: ' . config('broadcasting.connections.reverb.options.host') . "\n";
echo '   Port: ' . config('broadcasting.connections.reverb.options.port') . "\n";
echo '   Scheme: ' . config('broadcasting.connections.reverb.options.scheme') . "\n";
echo "\n";

// 3. Check test customer
echo "3. Test Customer Check:\n";
$customer = \App\Models\Customer::where('phone_number', '65656565')->first();
if ($customer) {
    echo "   ✓ Customer #{$customer->id} exists\n";
    echo "   Phone: {$customer->phone_number}\n";

    // 4. Generate test token
    echo "\n4. Token Generation Test:\n";
    $token = $customer->createToken('broadcast-test')->plainTextToken;
    echo "   ✓ Token generated successfully\n";
    echo '   Token: ' . substr($token, 0, 30) . "...\n";

    // 5. Test token lookup
    echo "\n5. Token Validation Test:\n";
    $personalAccessToken = Laravel\Sanctum\PersonalAccessToken::findToken($token);
    if ($personalAccessToken) {
        echo "   ✓ Token is valid\n";
        echo '   Belongs to: ' . get_class($personalAccessToken->tokenable) . " #{$personalAccessToken->tokenable->id}\n";
    } else {
        echo "   ✗ Token validation failed\n";
    }

    // 6. Test database connection
    echo "\n6. Database Connection Test:\n";

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "   ✓ Database connected\n";
    } catch (\Exception $e) {
        echo '   ✗ Database connection failed: ' . $e->getMessage() . "\n";
    }

    // 7. Provide curl command for testing
    echo "\n7. Manual Test Command:\n";
    echo "   Run this curl command to test the broadcasting/auth endpoint:\n\n";

    $domain = config('app.domains.admin');
    $protocol = config('broadcasting.connections.reverb.options.scheme') === 'https' ? 'https' : 'http';
    $url = "{$protocol}://{$domain}";

    // Add port if not standard
    if (config('app.env') === 'local') {
        $url .= ':9000';
    }

    echo "   curl -X POST {$url}/broadcasting/auth \\\n";
    echo "     -H \"Authorization: Bearer {$token}\" \\\n";
    echo "     -H \"Accept: application/json\" \\\n";
    echo "     -H \"Content-Type: application/x-www-form-urlencoded\" \\\n";
    echo "     -d \"socket_id=123.456&channel_name=private-customer.{$customer->id}\"\n\n";

    echo "   Expected response (200 OK):\n";
    echo "   {\"auth\":\"your-app-key:signature\"}\n\n";

    // 8. Test the endpoint programmatically
    echo "8. Programmatic Endpoint Test:\n";

    try {
        $request = \Illuminate\Http\Request::create(
            '/broadcasting/auth',
            'POST',
            [
                'socket_id' => '123.456',
                'channel_name' => "private-customer.{$customer->id}",
            ],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => "Bearer {$token}",
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_HOST' => config('app.domains.admin'),
            ]
        );

        // Set up the user resolver
        $request->setUserResolver(function () use ($customer) {
            return $customer;
        });

        // Try to authorize the channel
        $response = \Illuminate\Support\Facades\Broadcast::auth($request);

        echo "   ✓ Broadcasting authorization successful!\n";
        if (is_array($response)) {
            echo '   Response: ' . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo '   Response: ' . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Broadcasting authorization failed\n";
        echo '   Error: ' . $e->getMessage() . "\n";
        echo '   Trace: ' . $e->getTraceAsString() . "\n";
    }

} else {
    echo "   ✗ Customer not found (phone: 65656565)\n";
    echo "   Please seed the database or create a test customer first.\n";
}

echo "\n=========================================\n";
echo "Diagnostic test complete.\n";
echo "=========================================\n";
