<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Events\Socket\Customer\TripCompletedEvent;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::BUSY->value,
    ]);
    $this->customer = Customer::factory()->create();

    // Helper function to create a trip with locations
    $this->createTripWithLocations = function (array $tripOverrides = [], array $locations = [], ?int $riderIdOverride = null) {
        $trip = Trip::query()->create(array_merge([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value,
        ], $tripOverrides));

        foreach ($locations as $location) {
            TripLocation::query()->create(array_merge([
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
            ], $location));
        }

        $tripRequest = TripRequest::query()->create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $riderIdOverride ?? $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        $trip->tripRequest = $tripRequest;

        return $trip;
    };
});

test('rider can mark single location trip as completed successfully', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'next_action',
                'trip_completed',
            ],
        ])
        ->assertJson([
            'data' => [
                'next_action' => null,
                'trip_completed' => true,
            ],
        ]);

    // Verify trip status updated to completed
    assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatusEnum::COMPLETED->value,
    ]);

    // Verify location status updated
    assertDatabaseHas('trip_locations', [
        'trip_id' => $trip->id,
        'type' => TripLocationTypeEnum::DESTINATION->value,
        'status' => TripLocationStatusEnum::COMPLETED->value,
    ]);

    // Verify rider status is back to online
    assertDatabaseHas('riders', [
        'id' => $this->rider->id,
        'status' => RiderStatusEnum::ONLINE->value,
    ]);
});

test('rider can complete first destination and get next action for second destination', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 2,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.4000,
                TripLocation::COLUMN_LONGITUDE => 48.0000,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'next_action' => 'complete',
                'trip_completed' => false,
            ],
        ]);

    // Verify trip is NOT completed yet
    assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatusEnum::COMPLETED->value,
    ]);

    // Verify first destination is completed
    assertDatabaseHas('trip_locations', [
        'trip_id' => $trip->id,
        'sequence' => 2,
        'status' => TripLocationStatusEnum::COMPLETED->value,
    ]);

    // Verify rider is still busy
    assertDatabaseHas('riders', [
        'id' => $this->rider->id,
        'status' => RiderStatusEnum::BUSY->value,
    ]);
});

test('rider can complete all locations and finish trip', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 2,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.4000,
                TripLocation::COLUMN_LONGITUDE => 48.0000,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'next_action' => null,
                'trip_completed' => true,
            ],
        ]);

    // Verify trip is completed
    assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatusEnum::COMPLETED->value,
    ]);

    // Verify rider is back to online
    assertDatabaseHas('riders', [
        'id' => $this->rider->id,
        'status' => RiderStatusEnum::ONLINE->value,
    ]);
});

test('rider cannot mark trip as completed that does not belong to them', function () {
    $otherRider = Rider::factory()->create();

    $trip = ($this->createTripWithLocations)(
        [
            Trip::COLUMN_RIDER_ID => $otherRider->{Rider::COLUMN_ID},
            Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value,
        ],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ],
        $otherRider->{Rider::COLUMN_ID}
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertForbidden();
});

test('rider cannot mark trip as completed with invalid status - draft', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('rider cannot mark trip as completed with invalid status - accepted rider', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('rider cannot mark trip as completed with invalid status - arrived', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ARRIVED->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('rider cannot mark trip as completed with invalid status - already completed', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('unauthenticated rider cannot mark trip as completed', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

    $response->assertUnauthorized();
});

test('trip completed event is dispatched when rider completes all trip locations', function () {
    Event::fake([TripCompletedEvent::class]);

    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest))
        ->assertOk();

    Event::assertDispatched(TripCompletedEvent::class, function ($event) use ($trip) {
        return $event->customerId === $trip->customer_id
            && $event->tripId === $trip->id
            && $event->riderId === $trip->rider_id;
    });
});

test('trip completed event is NOT dispatched when trip has more locations to complete', function () {
    Event::fake([TripCompletedEvent::class]);

    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::PICKED_UP->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.4000,
                TripLocation::COLUMN_LONGITUDE => 48.0000,
                TripLocation::COLUMN_SEQUENCE => 2,
            ],
        ]
    );

    actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest))
        ->assertOk();

    Event::assertNotDispatched(TripCompletedEvent::class);
});
