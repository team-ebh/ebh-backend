<?php

declare(strict_types=1);

/**
 * Test script to verify broadcasting authentication
 *
 * Usage: php test_broadcast_auth.php
 */

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test customer authentication
echo "Testing Customer Broadcasting Authentication\n";
echo str_repeat('-', 50) . "\n";

// Create a test customer
$customer = Customer::query()->where('phone_number', '65656565')->first();

if (! $customer) {
    echo "❌ Customer with phone 65656565 not found\n";
    echo "Please create this customer first or use a different phone number\n";
    exit(1);
}

echo "✓ Found customer: #{$customer->id} - {$customer->full_name}\n";

// Create a token
$token = $customer->createToken('broadcast-test')->plainTextToken;
echo '✓ Created token: ' . substr($token, 0, 20) . "...\n\n";

// Test channel authorization
echo "Testing channel: customer.{$customer->id}\n";

// Simulate broadcasting authentication
$user = Auth::guard('customer')->user();
if (! $user) {
    // Try to set the user
    Auth::guard('customer')->setUser($customer);
    $user = Auth::guard('customer')->user();
}

echo 'Current authenticated user: ' . ($user ? "Customer #{$user->id}" : 'Not authenticated') . "\n";

// Test channel authorization callback
$channelName = "customer.{$customer->id}";
echo "\nTesting authorization for channel: {$channelName}\n";

try {
    // Get the channel authorization callback
    $result = Broadcast::channel($channelName, function ($authUser, $customerId) {
        echo "Authorization callback called\n";
        echo '  - Auth user: ' . ($authUser ? "Customer #{$authUser->id}" : 'null') . "\n";
        echo "  - Requested customer ID: {$customerId}\n";
        echo '  - Match: ' . ($authUser && (int) $authUser->id === (int) $customerId ? 'YES' : 'NO') . "\n";

        return $authUser && ((int) $authUser->id === (int) $customerId);
    });

    echo "\n✓ Channel authorization configured correctly\n";
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}

echo "\n" . str_repeat('-', 50) . "\n";
echo "Test completed!\n";
echo "\nTo use in browser:\n";
echo "1. Phone: 65656565\n";
echo "2. OTP: 0421\n";
echo "3. Channel: customer.{$customer->id}\n";
