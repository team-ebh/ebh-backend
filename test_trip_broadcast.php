<?php

declare(strict_types=1);

/**
 * Test script to create and confirm a trip for broadcast testing
 *
 * This will:
 * 1. Get customer 2 token
 * 2. Find or create a DRAFT trip
 * 3. Confirm the trip
 * 4. Broadcast events will be sent to customer.2 and rider.{riderId} channels
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Actions\Api\V1\Customer\Trip\ConfirmTripAction;
use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;

echo '=== Trip Broadcast Test ===' . PHP_EOL . PHP_EOL;

// Get customer 2
$customer = Customer::find(2);
if (! $customer) {
    echo '❌ Customer 2 not found!' . PHP_EOL;
    exit(1);
}
echo "✅ Customer found: ID {$customer->id}, Phone: {$customer->phone_number}" . PHP_EOL;

// Find available riders
$riders = Rider::limit(3)->get();
echo '✅ Available riders: ' . $riders->count() . PHP_EOL;
foreach ($riders as $rider) {
    echo "   - Rider ID: {$rider->id}, Phone: {$rider->phone_number}" . PHP_EOL;
}
echo PHP_EOL;

// Find existing DRAFT trip or use latest trip
$trip = Trip::where('customer_id', 2)
    ->where('status', 1) // DRAFT
    ->first();

if (! $trip) {
    echo '⚠️  No DRAFT trip found for customer 2' . PHP_EOL;
    echo 'Looking for any trip...' . PHP_EOL;
    $trip = Trip::where('customer_id', 2)->latest()->first();

    if (! $trip) {
        echo '❌ No trips found for customer 2!' . PHP_EOL;
        echo 'Please create a trip first through the API or database.' . PHP_EOL;
        exit(1);
    }

    // Update to DRAFT status
    if ($trip->status->value !== 1) {
        echo 'Setting trip status to DRAFT...' . PHP_EOL;
        $trip->update(['status' => 1]);
    }
}

echo "✅ Trip found: ID {$trip->id}, Status: " . $trip->status->value . ", Customer ID: {$trip->customer_id}" . PHP_EOL;
echo PHP_EOL;

// Prepare DTO
$dto = new ConfirmTripDTO();
$dto->trip = $trip;

echo '🚀 Confirming trip...' . PHP_EOL;
echo '   This will broadcast to:' . PHP_EOL;
echo "   - customer.{$customer->id} (Customer channel)" . PHP_EOL;
echo '   - rider.{riderId} (For each eligible rider)' . PHP_EOL;
echo PHP_EOL;

try {
    // Confirm the trip using Laravel's container
    $action = app(ConfirmTripAction::class);

    $action($dto);

    echo '✅ Trip confirmed successfully!' . PHP_EOL;
    echo '✅ Broadcast events sent!' . PHP_EOL;
    echo PHP_EOL;

    // Refresh trip
    $trip->refresh();
    echo '📊 Trip Status: ' . $trip->status->value . PHP_EOL;

    // Check trip requests
    $tripRequests = \App\Models\TripRequest::where('trip_id', $trip->id)->get();
    echo "📊 Trip requests created: {$tripRequests->count()}" . PHP_EOL;

    if ($tripRequests->isNotEmpty()) {
        echo '   Riders who received broadcast:' . PHP_EOL;
        foreach ($tripRequests as $request) {
            echo "   - Rider ID: {$request->rider_id}, Status: " . $request->status->value . PHP_EOL;
        }
    }

    echo PHP_EOL;
    echo '🎉 Done! Check your broadcast monitor:' . PHP_EOL;
    echo '   http://admin.localhost:9000/broadcast-monitor/unified' . PHP_EOL;
    echo PHP_EOL;
    echo '   Connect to:' . PHP_EOL;
    echo "   - Customer ID: {$customer->id}" . PHP_EOL;
    if ($tripRequests->isNotEmpty()) {
        echo "   - Rider ID: {$tripRequests->first()->rider_id}" . PHP_EOL;
    }

} catch (\Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
    echo '   File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    exit(1);
}
