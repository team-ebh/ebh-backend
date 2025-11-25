<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_LATITUDE => 29.3800,
        Rider::COLUMN_LONGITUDE => 47.9800,
    ]);
    $this->customer = Customer::factory()->create();

    // Helper function to create a trip with locations
    $this->createTripWithLocations = function (array $tripOverrides = [], array $locations = [], ?int $riderIdOverride = null) {
        $trip = Trip::create(array_merge([
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
            TripLocation::create(array_merge([
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            ], $location));
        }

        $tripRequest = TripRequest::create([
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

test('rider can get estimated arrival time to origin location', function () {
    $trip = ($this->createTripWithLocations)([], [
        [
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
        ],
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.estimated_arrival_time', $trip->tripRequest));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'estimated_arrival_seconds',
            ],
        ])
        ->assertJson(
            fn ($json) => $json
                ->has('data')
                ->where('data.estimated_arrival_seconds', fn ($value) => is_int($value) && $value >= 0)
        );
});

test('rider can get estimated arrival time to destination location', function () {
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
        ]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.estimated_arrival_time', $trip->tripRequest));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'estimated_arrival_seconds',
            ],
        ]);
});

test('rider cannot get estimated arrival time for trip not belonging to them', function () {
    $otherRider = Rider::factory()->create([
        Rider::COLUMN_LATITUDE => 29.3800,
        Rider::COLUMN_LONGITUDE => 47.9800,
    ]);

    $trip = ($this->createTripWithLocations)(
        [],
        [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
        ],
        $otherRider->{Rider::COLUMN_ID}
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson(route('v1.riders.trips.requests.estimated_arrival_time', $trip->tripRequest));

    $response->assertForbidden(); // TripNotBelongToRiderException
});

test('unauthenticated rider cannot get estimated arrival time', function () {
    $trip = ($this->createTripWithLocations)([], [
        [
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
        ],
    ]);

    $response = postJson(route('v1.riders.trips.requests.estimated_arrival_time', $trip->tripRequest));

    $response->assertUnauthorized();
});
