<?php

declare(strict_types=1);

/**
 * Test Echo connection with detailed debugging
 */

use App\Models\Customer;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Testing Laravel Echo Connection\n";
echo str_repeat('=', 60) . "\n\n";

// Get customer
$customer = Customer::where('phone_number', '65656565')->first();
if (! $customer) {
    echo "❌ Customer not found\n";
    exit(1);
}

echo "✅ Customer found: #{$customer->id}\n";

// Create token
$token = $customer->createToken('echo-test')->plainTextToken;
echo '✅ Token created: ' . substr($token, 0, 30) . "...\n\n";

// Test 1: Broadcasting auth endpoint
echo "TEST 1: Broadcasting Auth Endpoint\n";
echo str_repeat('-', 60) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://admin.localhost:9000/broadcasting/auth',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'socket_id' => '123.456',
        'channel_name' => 'private-customer.' . $customer->id,
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_VERBOSE => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Status: {$httpCode}\n";
    echo "✅ Response: {$response}\n";
} else {
    echo "❌ Status: {$httpCode}\n";
    echo "❌ Response: {$response}\n";
    if ($error) {
        echo "❌ Error: {$error}\n";
    }
}

echo "\n";

// Test 2: Check Reverb server
echo "TEST 2: Reverb Server\n";
echo str_repeat('-', 60) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8080',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_HEADER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 404 || $httpCode === 426) {
    echo "✅ Reverb is responding (HTTP {$httpCode})\n";
} else {
    echo "⚠️  Reverb response: HTTP {$httpCode}\n";
}

echo "\n";

// Test 3: Channel authorization
echo "TEST 3: Channel Authorization\n";
echo str_repeat('-', 60) . "\n";

// Simulate Sanctum authentication
$personalAccessToken = Laravel\Sanctum\PersonalAccessToken::findToken($token);
if ($personalAccessToken) {
    echo "✅ Valid Sanctum token found\n";

    $tokenable = $personalAccessToken->tokenable;
    echo '✅ Token belongs to: ' . get_class($tokenable) . " #{$tokenable->id}\n";

    // Check if this is a customer
    if ($tokenable instanceof Customer) {
        echo "✅ Token is for Customer model\n";

        // Check channel authorization
        $channelName = "customer.{$customer->id}";
        echo "✅ Checking channel: {$channelName}\n";

        // This would normally be done by Laravel Broadcasting
        if ((int) $tokenable->id === (int) $customer->id) {
            echo "✅ Channel authorization would succeed\n";
        } else {
            echo "❌ Customer ID mismatch\n";
        }
    } else {
        echo "⚠️  Token is not for Customer model\n";
    }
} else {
    echo "❌ Invalid token\n";
}

echo "\n";
echo str_repeat('=', 60) . "\n";
echo "✅ All tests completed\n\n";

echo "📋 Next steps:\n";
echo "1. Open: http://admin.localhost:9000/broadcast-test/customer\n";
echo "2. Hard refresh (Ctrl+Shift+R)\n";
echo "3. Login with: 65656565 / 0421\n";
echo "4. Click Connect\n";
echo "5. Open browser console (F12) and check for errors\n";
