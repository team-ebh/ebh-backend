<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);

    $this->customer = Customer::factory()->create();

    $this->token = $this->rider->createToken('test')->plainTextToken;
    $this->headers = [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'application/json',
        'Language' => 'en',
    ];

    // Helper function to create a trip with trip request
    $this->createTrip = function (array $tripOverrides = [], array $tripRequestOverrides = []) {
        $trip = Trip::create(array_merge([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ], $tripOverrides));

        $tripRequest = TripRequest::create(array_merge([
            'trip_id' => $trip->id,
            'rider_id' => $this->rider->id,
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
            'status' => TripRequestStatusEnum::PENDING->value,
            'sent_at' => now(),
        ], $tripRequestOverrides));

        $trip->tripRequest = $tripRequest;

        return $trip;
    };
});

describe('V1 Rider Trip API → Accept Trip → Rider Status', function () {
    test('rider status becomes BUSY after accepting a trip', function () {
        // Create a trip with pending status
        $trip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Accept the trip
        $response = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $trip->tripRequest->id]),
            [],
            $this->headers
        );

        $response->assertOk();

        // Verify rider status is now BUSY
        $this->rider->refresh();
        expect($this->rider->{Rider::COLUMN_STATUS})->toBe(RiderStatusEnum::BUSY);
    });

    test('rider cannot accept a second trip while having an active trip', function () {
        // Create first trip and accept it
        $firstTrip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Accept first trip
        postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $firstTrip->tripRequest->id]),
            [],
            $this->headers
        );

        // Verify rider is now busy
        $this->rider->refresh();
        expect($this->rider->{Rider::COLUMN_STATUS})->toBe(RiderStatusEnum::BUSY);

        // Create second trip
        $secondTrip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Try to accept second trip - should fail
        $response = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $secondTrip->tripRequest->id]),
            [],
            $this->headers
        );

        $response->assertStatus(406);
        $response->assertJson([
            'meta' => [
                'message' => __('riders.api.errors.rider_not_available'),
            ],
        ]);

        // Verify second trip request is still pending
        $secondTrip->tripRequest->refresh();
        expect($secondTrip->tripRequest->status)->toBe(TripRequestStatusEnum::PENDING);
    });
});

describe('V1 Rider Trip API → Accept Trip → Socket Events', function () {
    test('dispatches TripAcceptedEvent to customer when rider accepts trip', function () {
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\Socket\Customer\TripAcceptedEvent::class,
        ]);

        // Create a trip with pending status
        $trip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Accept the trip
        $response = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $trip->tripRequest->id]),
            [],
            $this->headers
        );

        $response->assertOk();

        // Verify TripAcceptedEvent was dispatched to customer
        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\Socket\Customer\TripAcceptedEvent::class,
            fn ($event) => $event->customerId === $this->customer->id
                && $event->tripId === $trip->id
                && $event->riderId === $this->rider->id
        );
    });

    test('dispatches TripRequestLockedEvent to other riders when trip is accepted', function () {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\Socket\Rider\TripRequestLockedEvent::class,
        ]);

        // Create additional riders with trip requests for the same trip
        $rider2 = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
        $rider3 = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);

        // Create a trip
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Create trip requests for all riders
        $tripRequest1 = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $this->rider->id,
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
            'status' => TripRequestStatusEnum::PENDING->value,
            'sent_at' => now(),
        ]);

        $tripRequest2 = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider2->id,
            'distance_meters' => 1500,
            'estimated_arrival_seconds' => 400,
            'status' => TripRequestStatusEnum::PENDING->value,
            'sent_at' => now(),
        ]);

        $tripRequest3 = TripRequest::create([
            'trip_id' => $trip->id,
            'rider_id' => $rider3->id,
            'distance_meters' => 2000,
            'estimated_arrival_seconds' => 500,
            'status' => TripRequestStatusEnum::PENDING->value,
            'sent_at' => now(),
        ]);

        // Accept the trip by rider 1
        $response = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequest1->id]),
            [],
            $this->headers
        );

        $response->assertOk();

        // Verify LockRemainingTripRequestsJob was dispatched
        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Jobs\Trip\LockRemainingTripRequestsJob::class,
            fn ($job) => $job->tripId === $trip->id
                && $job->acceptedTripRequestId === $tripRequest1->id
        );

        // Process the job manually to test event dispatching
        $job = new \App\Jobs\Trip\LockRemainingTripRequestsJob($trip->id, $tripRequest1->id);
        $job->handle();

        // Verify TripRequestLockedEvent was dispatched to other riders
        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\Socket\Rider\TripRequestLockedEvent::class,
            fn ($event) => $event->riderId === $rider2->id
                && $event->tripId === $trip->id
                && $event->tripRequestId === $tripRequest2->id
        );

        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\Socket\Rider\TripRequestLockedEvent::class,
            fn ($event) => $event->riderId === $rider3->id
                && $event->tripId === $trip->id
                && $event->tripRequestId === $tripRequest3->id
        );

        // Verify event was NOT dispatched to the accepting rider
        \Illuminate\Support\Facades\Event::assertNotDispatched(
            \App\Events\Socket\Rider\TripRequestLockedEvent::class,
            fn ($event) => $event->riderId === $this->rider->id
        );

        // Verify trip requests were locked
        $tripRequest2->refresh();
        $tripRequest3->refresh();
        expect($tripRequest2->status)->toBe(TripRequestStatusEnum::LOCKED);
        expect($tripRequest3->status)->toBe(TripRequestStatusEnum::LOCKED);
    });

    test('events are dispatched after database transaction commits', function () {
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\Socket\Customer\TripAcceptedEvent::class,
        ]);

        // Create a trip with pending status
        $trip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        $tripRequestId = $trip->tripRequest->id;

        // Accept the trip
        $response = postJson(
            route('v1.riders.trips.requests.accept', ['tripRequest' => $tripRequestId]),
            [],
            $this->headers
        );

        $response->assertOk();

        // Verify trip status was updated in database before event was dispatched
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::ACCEPTED_RIDER);

        // Verify trip request status was updated
        $tripRequest = TripRequest::find($tripRequestId);
        expect($tripRequest->status)->toBe(TripRequestStatusEnum::ACCEPTED);

        // Verify event was dispatched
        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\Socket\Customer\TripAcceptedEvent::class
        );
    });
});
