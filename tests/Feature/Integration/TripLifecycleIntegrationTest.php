<?php

declare(strict_types=1);

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Events\Socket\Customer\TripAcceptedEvent;
use App\Events\Socket\Customer\TripArrivedEvent;
use App\Events\Socket\Customer\TripCompletedEvent;
use App\Events\Socket\Customer\TripPickedUpEvent;
use App\Events\Socket\Rider\TripRequestLockedEvent;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\postJson;

describe('Complete Trip Lifecycle Integration Tests', function () {
    it('completes full trip lifecycle from creation to completion', function () {
        Event::fake();

        // Step 1: Create customer and authenticate
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        // Step 2: Customer creates a trip
        $tripData = [
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'origin_location_title' => 'Pickup Location',
            'origin_location_sub_title' => 'Building A',
            'origin_latitude' => 29.3759,
            'origin_longitude' => 47.9774,
            'destination_location_title' => 'Destination Location',
            'destination_location_sub_title' => 'Building B',
            'destination_latitude' => 29.3117,
            'destination_longitude' => 47.4818,
        ];

        $createResponse = postJson(route('v1.customers.trips.store'), $tripData, $customerHeaders);
        expect($createResponse->status())->toBe(200);

        $tripId = $createResponse->json('data.id');
        $trip = Trip::find($tripId);

        // Verify trip is created with DRAFT status
        expect($trip)->not->toBeNull()
            ->and($trip->status)->toBe(TripStatusEnum::DRAFT)
            ->and($trip->customer_id)->toBe($customer->id)
            ->and($trip->locations()->count())->toBe(2);

        // Step 3: Customer confirms the trip
        $confirmResponse = postJson(
            route('v1.customers.trips.confirm', ['trip' => $trip->id]),
            ['payment_method' => PaymentMethodEnum::CASH->value],
            $customerHeaders
        );
        expect($confirmResponse->status())->toBe(200);

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::PENDING_RIDER);

        // Verify TripRequests were broadcast to eligible riders
        // (We'll create riders and trip requests manually for this test)
        $riders = collect();
        for ($i = 0; $i < 3; $i++) {
            $rider = Rider::factory()->create([
                'status' => RiderStatusEnum::ONLINE,
            ]);
            $riders->push($rider);

            TripRequest::create([
                'trip_id' => $trip->id,
                'rider_id' => $rider->id,
                'status' => TripRequestStatusEnum::PENDING,
                'sent_at' => now(),
                'distance_meters' => 1000,
                'estimated_arrival_seconds' => 300,
            ]);
        }

        // Verify TripRequests were created
        expect($trip->tripRequests()->count())->toBe(3);

        // Step 4: Multiple riders try to accept concurrently (race condition)
        $acceptanceResults = [];

        foreach ($riders as $index => $rider) {
            $riderHeaders = [
                'Authorization' => 'Bearer ' . $rider->createToken('test')->plainTextToken,
            ];

            $tripRequest = TripRequest::query()
                ->where('trip_id', $trip->id)
                ->where('rider_id', $rider->id)
                ->first();

            $acceptResponse = postJson(
                route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequest->id]),
                [],
                $riderHeaders
            );

            $acceptanceResults[$index] = [
                'status' => $acceptResponse->status(),
                'rider_id' => $rider->id,
            ];
        }

        // Only one rider should successfully accept
        $successCount = collect($acceptanceResults)->filter(fn ($result) => $result['status'] === 200)->count();
        expect($successCount)->toBe(1);

        // Find the accepting rider
        $trip->refresh();
        expect($trip->rider_id)->not->toBeNull()
            ->and($trip->status)->toBe(TripStatusEnum::ACCEPTED_RIDER);

        $acceptingRider = Rider::find($trip->rider_id);
        expect($acceptingRider->status)->toBe(RiderStatusEnum::BUSY);

        // Note: RiderStatusLog testing is done in ObserverTest.php

        // Verify TripAcceptedEvent would be dispatched
        Event::assertDispatched(
            TripAcceptedEvent::class,
            fn ($event) => $event->customerId === $customer->id
                && $event->tripId === $trip->id
                && $event->riderId === $acceptingRider->id
        );

        // Verify TripRequestLockedEvent would be dispatched to other riders
        Event::assertDispatched(
            TripRequestLockedEvent::class,
            function ($event) use ($trip, $acceptingRider, $riders) {
                $otherRiderIds = $riders->pluck('id')->filter(fn ($id) => $id !== $acceptingRider->id)->toArray();

                return $event->tripId === $trip->id && in_array($event->riderId, $otherRiderIds);
            }
        );

        // Step 5: Rider arrives at pickup location
        $riderHeaders = [
            'Authorization' => 'Bearer ' . $acceptingRider->createToken('test')->plainTextToken,
        ];

        $acceptedTripRequest = TripRequest::query()
            ->where('trip_id', $trip->id)
            ->where('rider_id', $acceptingRider->id)
            ->first();

        $pickupLocation = $trip->locations()->where('type', TripLocationTypeEnum::ORIGIN)->first();

        $arriveResponse = postJson(
            route('v1.riders.trips.requests.arrived', ['tripRequest' => $acceptedTripRequest->id]),
            ['location_id' => $pickupLocation->id],
            $riderHeaders
        );
        expect($arriveResponse->status())->toBe(200);

        // Verify location status updated
        $pickupLocation->refresh();
        expect($pickupLocation->status)->toBe(TripLocationStatusEnum::ARRIVED);

        // Verify trip status updated to ARRIVED
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::ARRIVED);

        // Verify TripArrivedEvent would be dispatched
        Event::assertDispatched(
            TripArrivedEvent::class,
            fn ($event) => $event->customerId === $customer->id
                && $event->tripId === $trip->id
        );

        // Step 6: Rider picks up customer
        $pickupResponse = postJson(
            route('v1.riders.trips.requests.picked-up', ['tripRequest' => $acceptedTripRequest->id]),
            ['location_id' => $pickupLocation->id],
            $riderHeaders
        );
        expect($pickupResponse->status())->toBe(200);

        // Verify location status and trip status
        $pickupLocation->refresh();
        expect($pickupLocation->status)->toBe(TripLocationStatusEnum::PICKED_UP);

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::IN_PROGRESS);

        // Verify TripPickedUpEvent would be dispatched
        Event::assertDispatched(
            TripPickedUpEvent::class,
            fn ($event) => $event->customerId === $customer->id
                && $event->tripId === $trip->id
        );

        // Step 7: Rider completes the trip at destination
        $destinationLocation = $trip->locations()->where('type', TripLocationTypeEnum::DESTINATION)->first();

        $completeResponse = postJson(
            route('v1.riders.trips.requests.completed', ['tripRequest' => $acceptedTripRequest->id]),
            ['location_id' => $destinationLocation->id],
            $riderHeaders
        );
        expect($completeResponse->status())->toBe(200);

        // Verify destination location status
        $destinationLocation->refresh();
        expect($destinationLocation->status)->toBe(TripLocationStatusEnum::COMPLETED);

        // Verify trip status is COMPLETED
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::COMPLETED);

        // Verify rider status is back to ONLINE
        $acceptingRider->refresh();
        expect($acceptingRider->status)->toBe(RiderStatusEnum::ONLINE);

        // Note: RiderStatusLog testing is done in ObserverTest.php

        // Verify TripCompletedEvent would be dispatched
        Event::assertDispatched(
            TripCompletedEvent::class,
            fn ($event) => $event->customerId === $customer->id
                && $event->tripId === $trip->id
        );

        // Note: TripStatusLog testing is done in ObserverTest.php
        // We verify the workflow completed successfully via trip status and rider status
    });

    it('handles customer cancellation during trip lifecycle', function () {
        Event::fake();

        // Create customer and trip
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

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

        TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::PENDING,
        ]);

        // Create riders and trip requests
        $riders = collect();
        for ($i = 0; $i < 2; $i++) {
            $rider = Rider::factory()->create([
                'status' => RiderStatusEnum::ONLINE,
            ]);
            $riders->push($rider);

            TripRequest::create([
                'trip_id' => $trip->id,
                'rider_id' => $rider->id,
                'status' => TripRequestStatusEnum::PENDING,
                'sent_at' => now(),
            ]);
        }

        // One rider accepts
        $acceptingRider = $riders->first();
        $riderHeaders = [
            'Authorization' => 'Bearer ' . $acceptingRider->createToken('test')->plainTextToken,
        ];

        $tripRequest = TripRequest::query()
            ->where('trip_id', $trip->id)
            ->where('rider_id', $acceptingRider->id)
            ->first();

        $acceptResponse = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequest->id]),
            [],
            $riderHeaders
        );
        expect($acceptResponse->status())->toBe(200);

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::ACCEPTED_RIDER)
            ->and($trip->rider_id)->toBe($acceptingRider->id);

        $acceptingRider->refresh();
        expect($acceptingRider->status)->toBe(RiderStatusEnum::BUSY);

        // Customer cancels the trip
        $cancelResponse = postJson(
            route('v1.customers.trips.cancel', ['trip' => $trip->id]),
            [],
            $customerHeaders
        );
        expect($cancelResponse->status())->toBe(200);

        // Verify trip is cancelled
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);

        // Verify rider status is back to ONLINE
        $acceptingRider->refresh();
        expect($acceptingRider->status)->toBe(RiderStatusEnum::ONLINE);

        // Note: TripStatusLog testing is done in ObserverTest.php
    });

    it('completes multi-location trip lifecycle correctly', function () {
        Event::fake();

        // Create customer
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        // Create trip with multiple destinations
        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => null,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 10.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Create origin + 3 destinations
        $pickupLocation = TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup Location',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::PENDING,
        ]);

        $destinations = collect();
        for ($i = 1; $i <= 3; $i++) {
            $destination = TripLocation::create([
                'trip_id' => $trip->id,
                'type' => TripLocationTypeEnum::DESTINATION,
                'location_title' => "Destination $i",
                'latitude' => 29.3759 + ($i * 0.01),
                'longitude' => 47.9774 + ($i * 0.01),
                'sequence' => $i + 1,
                'status' => TripLocationStatusEnum::PENDING,
            ]);
            $destinations->push($destination);
        }

        // Rider accepts and completes full flow
        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::ONLINE,
        ]);

        TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::PENDING,
            'sent_at' => now(),
        ]);

        $riderHeaders = [
            'Authorization' => 'Bearer ' . $rider->createToken('test')->plainTextToken,
        ];

        $tripRequest = TripRequest::where('trip_id', $trip->id)->first();

        // Accept
        postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequest->id]),
            [],
            $riderHeaders
        )->assertOk();

        // Arrive at pickup
        postJson(
            route('v1.riders.trips.requests.arrived', ['tripRequest' => $tripRequest->id]),
            ['location_id' => $pickupLocation->id],
            $riderHeaders
        )->assertOk();

        // Pick up customer
        postJson(
            route('v1.riders.trips.requests.picked-up', ['tripRequest' => $tripRequest->id]),
            ['location_id' => $pickupLocation->id],
            $riderHeaders
        )->assertOk();

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::IN_PROGRESS);

        // Complete each destination one by one
        foreach ($destinations as $index => $destination) {
            $completeResponse = postJson(
                route('v1.riders.trips.requests.completed', ['tripRequest' => $tripRequest->id]),
                ['location_id' => $destination->id],
                $riderHeaders
            );
            expect($completeResponse->status())->toBe(200);

            $destination->refresh();

            // Check if this is the last destination
            if ($index === $destinations->count() - 1) {
                // Last destination should be COMPLETED
                expect($destination->status)->toBe(TripLocationStatusEnum::COMPLETED);
                // Last destination - trip should be completed
                $trip->refresh();
                expect($trip->status)->toBe(TripStatusEnum::COMPLETED);

                // Verify TripCompletedEvent was dispatched
                Event::assertDispatched(
                    TripCompletedEvent::class,
                    fn ($event) => $event->customerId === $customer->id
                        && $event->tripId === $trip->id
                );

                // Rider should be ONLINE again
                $rider->refresh();
                expect($rider->status)->toBe(RiderStatusEnum::ONLINE);
            } else {
                // Intermediate destinations should be DROPPED_OFF
                expect($destination->status)->toBe(TripLocationStatusEnum::DROPPED_OFF);

                // Not last destination - trip should still be IN_PROGRESS
                $trip->refresh();
                expect($trip->status)->toBe(TripStatusEnum::IN_PROGRESS);

                // TripCompletedEvent should NOT be dispatched yet
                Event::assertDispatchedTimes(TripCompletedEvent::class, 0);

                // Rider should still be BUSY
                $rider->refresh();
                expect($rider->status)->toBe(RiderStatusEnum::BUSY);
            }
        }

        // Verify all destinations are either DROPPED_OFF or COMPLETED
        $droppedOffCount = $trip->locations()
            ->where('type', TripLocationTypeEnum::DESTINATION)
            ->where('status', TripLocationStatusEnum::DROPPED_OFF)
            ->count();

        $completedCount = $trip->locations()
            ->where('type', TripLocationTypeEnum::DESTINATION)
            ->where('status', TripLocationStatusEnum::COMPLETED)
            ->count();

        expect($droppedOffCount)->toBe(2)  // First 2 destinations
            ->and($completedCount)->toBe(1);  // Last destination
    });

    it('handles rider cancellation when trip is in ARRIVED status', function () {
        Event::fake();

        // Create customer and rider
        $customer = Customer::factory()->create();
        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::ONLINE,
        ]);

        $riderHeaders = [
            'Authorization' => 'Bearer ' . $rider->createToken('test')->plainTextToken,
        ];

        // Create trip with ARRIVED status
        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::ARRIVED->value,
        ]);

        TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::ARRIVED,
        ]);

        $tripRequest = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::ACCEPTED,
            'sent_at' => now(),
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
        ]);

        // Update rider status to BUSY
        $rider->update(['status' => RiderStatusEnum::BUSY]);

        // Rider cancels the trip
        $cancelResponse = postJson(
            route('v1.riders.trips.requests.cancel', ['tripRequest' => $tripRequest->id]),
            [],
            $riderHeaders
        );
        expect($cancelResponse->status())->toBe(200);

        // Verify trip is cancelled
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELLED_BY_RIDER);

        // Verify rider status is back to ONLINE
        $rider->refresh();
        expect($rider->status)->toBe(RiderStatusEnum::ONLINE);

        // Verify trip request is cancelled
        $tripRequest->refresh();
        expect($tripRequest->status)->toBe(TripRequestStatusEnum::CANCELLED);
    });

    it('handles customer cancellation when trip is in ARRIVED status', function () {
        Event::fake();

        // Create customer and rider
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::BUSY,
        ]);

        // Create trip with ARRIVED status
        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'payment_method' => PaymentMethodEnum::CASH,
            'status' => TripStatusEnum::ARRIVED->value,
        ]);

        TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::ARRIVED,
        ]);

        TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::ACCEPTED,
            'sent_at' => now(),
        ]);

        // Customer cancels the trip
        $cancelResponse = postJson(
            route('v1.customers.trips.cancel', ['trip' => $trip->id]),
            [],
            $customerHeaders
        );
        expect($cancelResponse->status())->toBe(200);

        // Verify trip is cancelled
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);

        // Verify rider status is back to ONLINE
        $rider->refresh();
        expect($rider->status)->toBe(RiderStatusEnum::ONLINE);
    });

    it('allows customer to get rider location when trip is ARRIVED', function () {
        // Create customer and rider
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::BUSY,
            'latitude' => 29.3759,
            'longitude' => 47.9774,
        ]);

        // Create trip with ARRIVED status
        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'payment_method' => PaymentMethodEnum::CASH,
            'status' => TripStatusEnum::ARRIVED->value,
        ]);

        TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::ARRIVED,
        ]);

        // Customer gets rider location
        $locationResponse = \Pest\Laravel\getJson(
            route('v1.customers.trips.rider-location', ['trip' => $trip->id]),
            $customerHeaders
        );
        expect($locationResponse->status())->toBe(200)
            ->and($locationResponse->json('data.latitude'))->toBe(29.3759)
            ->and($locationResponse->json('data.longitude'))->toBe(47.9774);
    });

    it('allows customer to get trip status when trip is ARRIVED', function () {
        // Create customer and rider
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::BUSY,
            'latitude' => 29.3759,
            'longitude' => 47.9774,
        ]);

        // Create trip with ARRIVED status
        $trip = Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'payment_method' => PaymentMethodEnum::CASH,
            'status' => TripStatusEnum::ARRIVED->value,
        ]);

        $pickupLocation = TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup Location',
            'location_sub_title' => 'Building A',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::ARRIVED,
        ]);

        $destinationLocation = TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::DESTINATION,
            'location_title' => 'Destination Location',
            'location_sub_title' => 'Building B',
            'latitude' => 29.3117,
            'longitude' => 47.4818,
            'sequence' => 2,
            'status' => TripLocationStatusEnum::PENDING,
        ]);

        TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::ACCEPTED,
            'sent_at' => now(),
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
        ]);

        // Customer gets trip status
        $statusResponse = \Pest\Laravel\getJson(
            route('v1.customers.trips.status', ['trip' => $trip->id]),
            $customerHeaders
        );
        expect($statusResponse->status())->toBe(200)
            ->and($statusResponse->json('data.found'))->toBeTrue()
            ->and($statusResponse->json('data.rider'))->not->toBeNull();
    });

    it('verifies complete trip status progression through lifecycle', function () {
        Event::fake();

        // Create customer and rider
        $customer = Customer::factory()->create();
        $customerHeaders = [
            'Authorization' => 'Bearer ' . $customer->createToken('test')->plainTextToken,
        ];

        $rider = Rider::factory()->create([
            'status' => RiderStatusEnum::ONLINE,
        ]);
        $riderHeaders = [
            'Authorization' => 'Bearer ' . $rider->createToken('test')->plainTextToken,
        ];

        // Create trip
        $trip = Trip::create([
            'customer_id' => $customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::DRAFT->value,
        ]);

        $pickupLocation = TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::ORIGIN,
            'location_title' => 'Pickup',
            'latitude' => 29.3759,
            'longitude' => 47.9774,
            'sequence' => 1,
            'status' => TripLocationStatusEnum::PENDING,
        ]);

        $destinationLocation = TripLocation::create([
            'trip_id' => $trip->id,
            'type' => TripLocationTypeEnum::DESTINATION,
            'location_title' => 'Destination',
            'latitude' => 29.3117,
            'longitude' => 47.4818,
            'sequence' => 2,
            'status' => TripLocationStatusEnum::PENDING,
        ]);

        // Status 1: DRAFT
        expect($trip->status)->toBe(TripStatusEnum::DRAFT);

        // Status 2: PENDING_RIDER (after confirmation)
        postJson(
            route('v1.customers.trips.confirm', ['trip' => $trip->id]),
            ['payment_method' => PaymentMethodEnum::CASH->value],
            $customerHeaders
        )->assertOk();

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::PENDING_RIDER);

        // Create trip request for rider
        $tripRequest = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider->id,
            'status' => TripRequestStatusEnum::PENDING,
            'sent_at' => now(),
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
        ]);

        // Status 3: ACCEPTED_RIDER (after rider accepts)
        postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequest->id]),
            [],
            $riderHeaders
        )->assertOk();

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::ACCEPTED_RIDER);

        // Status 4: ARRIVED (after rider arrives)
        postJson(
            route('v1.riders.trips.requests.arrived', ['tripRequest' => $tripRequest->id]),
            ['location_id' => $pickupLocation->id],
            $riderHeaders
        )->assertOk();

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::ARRIVED);

        // Status 5: IN_PROGRESS (after rider picks up customer)
        postJson(
            route('v1.riders.trips.requests.picked-up', ['tripRequest' => $tripRequest->id]),
            ['location_id' => $pickupLocation->id],
            $riderHeaders
        )->assertOk();

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::IN_PROGRESS);

        // Status 6: COMPLETED (after rider completes trip)
        postJson(
            route('v1.riders.trips.requests.completed', ['tripRequest' => $tripRequest->id]),
            ['location_id' => $destinationLocation->id],
            $riderHeaders
        )->assertOk();

        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::COMPLETED);

        // Verify all 6 statuses were encountered
        // DRAFT -> PENDING_RIDER -> ACCEPTED_RIDER -> ARRIVED -> IN_PROGRESS -> COMPLETED
    });
});
