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
use App\Events\Socket\Customer\TripPickedUpEvent;
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
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ], $tripOverrides));

        foreach ($locations as $location) {
            TripLocation::query()->create(array_merge([
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
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

test('rider can mark trip as picked up at origin location successfully', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ARRIVED->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
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
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'next_action',
            ],
        ]);

    // Verify trip status updated
    assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatusEnum::IN_PROGRESS->value,
    ]);

    // Verify origin location status updated
    assertDatabaseHas('trip_locations', [
        'trip_id' => $trip->id,
        'type' => TripLocationTypeEnum::ORIGIN->value,
        'status' => TripLocationStatusEnum::PICKED_UP->value,
    ]);
});

test('rider can mark trip as picked up and get next action', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ARRIVED->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
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
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'next_action' => 'complete',
            ],
        ]);
});

test('rider cannot mark trip as picked up that does not belong to them', function () {
    $otherRider = Rider::factory()->create();

    $trip = ($this->createTripWithLocations)(
        [
            Trip::COLUMN_RIDER_ID => $otherRider->{Rider::COLUMN_ID},
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ],
        $otherRider->{Rider::COLUMN_ID}
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertForbidden();
});

test('rider cannot mark trip as picked up with invalid status - draft', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('rider cannot mark trip as picked up with invalid status - accepted rider', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('rider cannot mark trip as picked up with invalid status - already on trip', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('rider cannot mark trip as picked up with invalid status - completed', function () {
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
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertStatus(406); // InvalidTripActionException
});

test('unauthenticated rider cannot mark trip as picked up', function () {
    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    $response = postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

    $response->assertUnauthorized();
});

test('trip picked up event is dispatched when rider marks trip as picked up', function () {
    Event::fake([TripPickedUpEvent::class]);

    $trip = ($this->createTripWithLocations)(
        [Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::ARRIVED->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ]
    );

    actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest))
        ->assertOk();

    Event::assertDispatched(TripPickedUpEvent::class, function ($event) use ($trip) {
        return $event->customerId === $trip->customer_id
            && $event->tripId === $trip->id
            && $event->riderId === $this->rider->id;
    });
});
