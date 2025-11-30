<?php

declare(strict_types=1);

/**
 * Simple broadcast test - directly broadcast events
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\Socket\Customer\TripSearchingForRiderEvent;
use App\Events\Socket\Rider\NewTripRequestEvent;

echo '=== Simple Broadcast Test ===' . PHP_EOL . PHP_EOL;

// Test 1: Customer Event (Direct broadcast - no queue)
echo '1️⃣  Broadcasting to Customer Channel...' . PHP_EOL;

try {
    broadcast(new TripSearchingForRiderEvent(
        customerId: 2,
        tripId: 999,
        riderCount: 5,
        radiusMeters: 1000,
        searchAttempt: 1
    ));
    echo '   ✅ Customer event broadcasted!' . PHP_EOL;
    echo '   📡 Channels: customer.2 and monitor.customer.2' . PHP_EOL;
} catch (\Exception $e) {
    echo '   ❌ Error: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Rider Event (Queued broadcast)
echo '2️⃣  Broadcasting to Rider Channel...' . PHP_EOL;

try {
    $trip = \App\Models\Trip::where('customer_id', 2)->first();

    if (! $trip) {
        echo '   ⚠️  No trip found, creating mock trip...' . PHP_EOL;
        $trip = new \App\Models\Trip();
        $trip->id = 999;
        $trip->customer_id = 2;
        $trip->status = \App\Enums\Trip\TripStatusEnum::DRAFT;
    }

    broadcast(new NewTripRequestEvent(
        riderId: 1,
        trip: $trip
    ));

    echo '   ✅ Rider event queued for broadcast!' . PHP_EOL;
    echo '   📡 Channels: rider.1 and monitor.rider.1' . PHP_EOL;
    echo '   ⏳ Note: This event uses ShouldQueue, check queue worker!' . PHP_EOL;
} catch (\Exception $e) {
    echo '   ❌ Error: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Check queue
echo '3️⃣  Checking Queue...' . PHP_EOL;
$queueSize = \Illuminate\Support\Facades\Queue::size();
echo "   📊 Jobs in queue: {$queueSize}" . PHP_EOL;

if ($queueSize > 0) {
    echo '   ⚠️  Make sure queue worker is running: php artisan queue:work' . PHP_EOL;
}

echo PHP_EOL;

// Check broadcasting config
echo '4️⃣  Broadcasting Configuration:' . PHP_EOL;
echo '   Driver: ' . config('broadcasting.default') . PHP_EOL;
echo '   Reverb Host: ' . config('broadcasting.connections.reverb.options.host') . PHP_EOL;
echo '   Reverb Port: ' . config('broadcasting.connections.reverb.options.port') . PHP_EOL;

echo PHP_EOL;
echo '🎯 Test complete!' . PHP_EOL;
echo PHP_EOL;
echo '📝 Next steps:' . PHP_EOL;
echo '   1. Open: http://admin.localhost:9000/broadcast-monitor/unified' . PHP_EOL;
echo '   2. Select "Monitor Channels"' . PHP_EOL;
echo '   3. Connect to Customer ID: 2' . PHP_EOL;
echo '   4. Connect to Rider ID: 1' . PHP_EOL;
echo '   5. Run this script again and watch for events!' . PHP_EOL;
