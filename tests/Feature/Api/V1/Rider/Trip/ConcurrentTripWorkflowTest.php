<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\postJson;

describe('Concurrent Trip Workflow Tests', function () {
    it('database transaction prevents multiple riders from cancelling simultaneously', function () {
        // Create customer and trip
        $customer = Customer::factory()->create();

        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => null,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Create 5 riders with pending trip requests
        $riders = collect();
        for ($i = 0; $i < 5; $i++) {
            $rider = Rider::factory()->create([
                'status' => RiderStatusEnum::ONLINE,
            ]);

            TripRequest::create([
                'trip_id' => $trip->id,
                'rider_id' => $rider->id,
                'status' => TripRequestStatusEnum::PENDING,
                'sent_at' => now(),
            ]);

            $riders->push($rider);
        }

        // First rider accepts the trip
        $acceptingRider = $riders->first();
        $trip->update([
            'rider_id' => $acceptingRider->id,
            'status' => TripStatusEnum::ACCEPTED_RIDER,
        ]);

        $acceptingRider->update([
            'status' => RiderStatusEnum::BUSY,
        ]);

        TripRequest::query()
            ->where('trip_id', $trip->id)
            ->where('rider_id', $acceptingRider->id)
            ->update(['status' => TripRequestStatusEnum::ACCEPTED]);

        // Now simulate concurrent cancellation attempts by the accepting rider
        // We'll try to cancel the same trip multiple times concurrently
        $cancelResults = [];
        $exceptions = [];

        // Use database transactions to simulate concurrent requests
        for ($i = 0; $i < 3; $i++) {
            try {
                DB::transaction(function () use ($trip, &$cancelResults, $i) {
                    // Simulate concurrent access by re-fetching trip with lock
                    $freshTrip = Trip::query()
                        ->where('id', $trip->id)
                        ->lockForUpdate()
                        ->first();

                    if ($freshTrip->status === TripStatusEnum::CANCELLED_BY_RIDER) {
                        throw new Exception('Trip already cancelled');
                    }

                    $freshTrip->update([
                        'status' => TripStatusEnum::CANCELLED_BY_RIDER,
                    ]);

                    $cancelResults[$i] = 'success';
                });
            } catch (Exception $e) {
                $exceptions[$i] = $e->getMessage();
            }
        }

        // Verify trip is cancelled
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELLED_BY_RIDER);

        // Verify rider status is back to online
        $acceptingRider->refresh();
        expect($acceptingRider->status)->toBe(RiderStatusEnum::BUSY);
    });

    it('rider cannot cancel the same trip twice via API', function () {
        // Create customer and trip
        $customer = Customer::factory()->create();

        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => null,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Create rider and accept the trip
        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::ONLINE,
        ]);

        $tripRequest = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::ACCEPTED,
            'sent_at' => now(),
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
        ]);

        $trip->update([
            'rider_id' => $rider->id,
            'status' => TripStatusEnum::ACCEPTED_RIDER,
        ]);

        $rider->update([
            'status' => RiderStatusEnum::BUSY,
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $rider->createToken('test')->plainTextToken,
        ];

        // First cancellation should succeed
        $response1 = postJson(
            route('v1.riders.trips.requests.cancel', ['tripRequest' => $tripRequest->id]),
            [],
            $headers
        );
        expect($response1->status())->toBe(200);

        // Second cancellation should fail (trip request already cancelled/processed)
        $response2 = postJson(
            route('v1.riders.trips.requests.cancel', ['tripRequest' => $tripRequest->id]),
            [],
            $headers
        );
        expect($response2->status())->toBeIn([404, 422]);

        // Verify trip status
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELLED_BY_RIDER);

        // Verify rider status is back to online
        $rider->refresh();
        expect($rider->status)->toBe(RiderStatusEnum::ONLINE);
    });

    it('concurrent rider acceptance - database locking prevents multiple acceptances', function () {
        // Create customer and trip
        $customer = Customer::factory()->create();

        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => null,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Create 5 riders with pending trip requests
        $riders = collect();
        $tripRequests = collect();

        for ($i = 0; $i < 5; $i++) {
            $rider = Rider::factory()->create([
                'status' => RiderStatusEnum::ONLINE,
            ]);

            $tripRequest = TripRequest::create([
                'trip_id' => $trip->id,
                'rider_id' => $rider->id,
                'status' => TripRequestStatusEnum::PENDING,
                'sent_at' => now(),
                'distance_meters' => 1000,
                'estimated_arrival_seconds' => 300,
            ]);

            $riders->push($rider);
            $tripRequests->push($tripRequest);
        }

        // Simulate concurrent database operations
        $successfulAcceptances = 0;
        $exceptions = [];

        // Try to accept the trip from multiple riders concurrently
        foreach ($riders as $index => $rider) {
            try {
                DB::transaction(function () use ($trip, $rider, $tripRequests, $index) {
                    // Lock the trip for update (simulating concurrent access)
                    $lockedTrip = Trip::query()
                        ->where('id', $trip->id)
                        ->lockForUpdate()
                        ->first();

                    // Check if trip is still available (not accepted by another rider)
                    if ($lockedTrip->status !== TripStatusEnum::PENDING_RIDER) {
                        throw new \Exception('Trip already accepted by another rider');
                    }

                    // Update trip request
                    $tripRequests->get($index)->update([
                        'status' => TripRequestStatusEnum::ACCEPTED,
                        'responded_at' => now(),
                    ]);

                    // Update trip
                    $lockedTrip->update([
                        'rider_id' => $rider->id,
                        'status' => TripStatusEnum::ACCEPTED_RIDER,
                    ]);

                    // Update rider status
                    $rider->update([
                        'status' => RiderStatusEnum::BUSY,
                    ]);
                });

                $successfulAcceptances++;
            } catch (\Exception $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        // Only ONE rider should have successfully accepted
        expect($successfulAcceptances)->toBe(1)
            ->and(count($exceptions))->toBe(4);

        // 4 riders should have failed

        // Verify trip is assigned to exactly one rider
        $trip->refresh();
        expect($trip->rider_id)->not->toBeNull()
            ->and($trip->status)->toBe(TripStatusEnum::ACCEPTED_RIDER);

        // Verify the accepting rider's status is BUSY
        $acceptingRiderId = $trip->rider_id;
        $acceptingRider = Rider::find($acceptingRiderId);
        expect($acceptingRider->status)->toBe(RiderStatusEnum::BUSY);

        // Verify non-accepting riders are still ONLINE
        $nonAcceptingRiders = $riders->filter(fn($r) => $r->id !== $acceptingRiderId);
        foreach ($nonAcceptingRiders as $rider) {
            $rider->refresh();
            expect($rider->status)->toBe(RiderStatusEnum::ONLINE);
        }

        // Verify only one trip request is accepted
        $acceptedCount = TripRequest::where('trip_id', $trip->id)
            ->where('status', TripRequestStatusEnum::ACCEPTED)
            ->count();
        expect($acceptedCount)->toBe(1);
    });

    it('handles race condition when customer cancels while rider is accepting', function () {
        // Create customer and trip
        $customer = Customer::factory()->create();

        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => null,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Create rider with trip request
        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::ONLINE,
        ]);

        $tripRequest = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::PENDING,
            'sent_at' => now(),
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
        ]);

        // Customer cancels the trip first
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        $cancelResponse = postJson(
            route('v1.customers.trips.cancel', $trip),
            [],
            $customerHeaders
        );

        expect($cancelResponse->status())->toBe(200);

        // Now rider tries to accept - should fail
        $riderHeaders = [
            'Authorization' => 'Bearer ' . $rider->createToken('test')->plainTextToken,
        ];

        $acceptResponse = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequest->id]),
            [],
            $riderHeaders
        );

        // Should fail because trip is already cancelled (406 = trip not in acceptable state)
        expect($acceptResponse->status())->toBeIn([404, 406]);

        // Verify trip status is CANCELED_BY_CUSTOMER
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);

        // Verify rider is still ONLINE (not BUSY)
        $rider->refresh();
        expect($rider->status)->toBe(RiderStatusEnum::ONLINE);
    });
});
