<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\RideTypeEnum;
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
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::BUSY,
    ]);
    $this->customer = Customer::factory()->create();

    // Helper to create ROUND_TRIP_WAIT trip with locations
    $this->createRoundTripWaitTrip = function (array $tripOverrides = [], array $locations = []) {
        $trip = Trip::create(array_merge([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ROUND_TRIP_WAIT->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value,
        ], $tripOverrides));

        foreach ($locations as $locationData) {
            TripLocation::create(array_merge([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => 'Test Location',
            ], $locationData));
        }

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->id,
            TripRequest::COLUMN_RIDER_ID => $this->rider->id,
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        $trip->tripRequest = $tripRequest;

        return $trip;
    };
});

describe('ROUND_TRIP_WAIT (Ride Type 3) - Complete Flow', function () {
    test('completing first destination returns pickup as next action', function () {
        // Setup: Origin is picked up, Destination 1 is pending, Destination 2 is pending
        $trip = ($this->createRoundTripWaitTrip)([], [
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
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]);

        // Action: Complete first destination
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

        // Assert: Next action should be pickup (not complete)
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'next_action' => 'pickup',
                    'trip_completed' => false,
                ],
            ]);

        // First destination should be DROPPED_OFF
        assertDatabaseHas('trip_locations', [
            'trip_id' => $trip->id,
            'sequence' => 2,
            'status' => TripLocationStatusEnum::DROPPED_OFF->value,
        ]);

        // Trip should still be in progress
        assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatusEnum::IN_PROGRESS->value,
        ]);
    });

    test('pickup at dropped off destination updates destination 2 to picked up and returns complete as next action', function () {
        // Setup: Origin is picked up, Destination 1 is dropped off (waiting), Destination 2 is pending
        $trip = ($this->createRoundTripWaitTrip)([], [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::DROPPED_OFF->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 2,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]);

        // Action: Pickup passenger at dropped off destination
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

        // Assert: Next action should be complete (for destination 2)
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'next_action' => 'complete',
                ],
            ]);

        // First destination should stay DROPPED_OFF (customer was waiting here)
        assertDatabaseHas('trip_locations', [
            'trip_id' => $trip->id,
            'sequence' => 2,
            'status' => TripLocationStatusEnum::DROPPED_OFF->value,
        ]);

        // Second destination should now be PICKED_UP (customer is on the way to final destination)
        assertDatabaseHas('trip_locations', [
            'trip_id' => $trip->id,
            'sequence' => 3,
            'status' => TripLocationStatusEnum::PICKED_UP->value,
        ]);
    });

    test('completing final destination finishes the trip', function () {
        // Setup: Origin picked up, Destination 1 dropped off (customer waited here), Destination 2 picked up (on the way to final)
        $trip = ($this->createRoundTripWaitTrip)([], [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::DROPPED_OFF->value,
                TripLocation::COLUMN_LATITUDE => 29.3900,
                TripLocation::COLUMN_LONGITUDE => 47.9900,
                TripLocation::COLUMN_SEQUENCE => 2,
            ],
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]);

        // Action: Complete final destination
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));

        // Assert: Trip should be completed
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'next_action' => null,
                    'trip_completed' => true,
                ],
            ]);

        // Final destination should be COMPLETED
        assertDatabaseHas('trip_locations', [
            'trip_id' => $trip->id,
            'sequence' => 3,
            'status' => TripLocationStatusEnum::COMPLETED->value,
        ]);

        // Trip should be completed
        assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        // Rider should be back online
        assertDatabaseHas('riders', [
            'id' => $this->rider->id,
            'status' => RiderStatusEnum::ONLINE->value,
        ]);
    });

    test('full ROUND_TRIP_WAIT flow from start to finish', function () {
        // Setup: Trip with all locations pending (starting fresh after accept)
        $trip = ($this->createRoundTripWaitTrip)([
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ], [
            [
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
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
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]);

        // Step 1: Arrive at origin
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.arrived', $trip->tripRequest));
        $response->assertOk()->assertJsonPath('data.next_action', 'pickup');

        // Step 2: Pickup at origin
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));
        $response->assertOk()->assertJsonPath('data.next_action', 'complete');

        // Step 3: Complete first destination (drop off customer)
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));
        $response->assertOk()
            ->assertJsonPath('data.next_action', 'pickup')
            ->assertJsonPath('data.trip_completed', false);

        // Step 4: Pickup at first destination (customer gets back in after waiting)
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));
        $response->assertOk()->assertJsonPath('data.next_action', 'complete');

        // Step 5: Complete final destination (trip ends)
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $trip->tripRequest));
        $response->assertOk()
            ->assertJsonPath('data.next_action', null)
            ->assertJsonPath('data.trip_completed', true);

        // Verify final state
        assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        assertDatabaseHas('riders', [
            'id' => $this->rider->id,
            'status' => RiderStatusEnum::ONLINE->value,
        ]);
    });
});

describe('ROUND_TRIP_WAIT - Validation', function () {
    test('cannot pickup at non-dropped-off destination', function () {
        // Setup: Destination 1 is still PENDING (not dropped off yet)
        $trip = ($this->createRoundTripWaitTrip)([], [
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
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_SEQUENCE => 3,
            ],
        ]);

        // Action: Try to pickup (should fail because current action should be complete, not pickup)
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.picked-up', $trip->tripRequest));

        // Should fail with 406 (Not Acceptable) or similar error
        $response->assertStatus(406);
    });
});

describe('ONE_WAY (Ride Type 1) - Standard Flow', function () {
    test('completing single destination finishes ONE_WAY trip', function () {
        // Setup: ONE_WAY trip with origin picked up, destination pending
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_SEQUENCE => 2,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->id,
            TripRequest::COLUMN_RIDER_ID => $this->rider->id,
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Action: Complete destination
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $tripRequest));

        // Assert: Trip should be completed
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'next_action' => null,
                    'trip_completed' => true,
                ],
            ]);

        assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);
    });
});

describe('ROUND_TRIP (Ride Type 2) - Two Destinations Flow', function () {
    test('completing first destination returns complete for second destination', function () {
        // Setup: ROUND_TRIP with origin picked up, two destinations pending
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ROUND_TRIP->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 8.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_SEQUENCE => 2,
            TripLocation::COLUMN_LOCATION_TITLE => 'First Destination',
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 3,
            TripLocation::COLUMN_LOCATION_TITLE => 'Final Destination (Back to Origin)',
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->id,
            TripRequest::COLUMN_RIDER_ID => $this->rider->id,
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Action: Complete first destination
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $tripRequest));

        // Assert: For ROUND_TRIP (not ROUND_TRIP_WAIT), next action should be complete
        // (passenger stays in car, no pickup needed)
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'next_action' => 'complete',
                    'trip_completed' => false,
                ],
            ]);

        // First destination should be DROPPED_OFF
        assertDatabaseHas('trip_locations', [
            'trip_id' => $trip->id,
            'sequence' => 2,
            'status' => TripLocationStatusEnum::DROPPED_OFF->value,
        ]);
    });

    test('completing second destination finishes ROUND_TRIP', function () {
        // Setup: ROUND_TRIP with first destination dropped off, second pending
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ROUND_TRIP->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 8.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::DROPPED_OFF->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_SEQUENCE => 2,
            TripLocation::COLUMN_LOCATION_TITLE => 'First Destination',
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 3,
            TripLocation::COLUMN_LOCATION_TITLE => 'Final Destination',
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->id,
            TripRequest::COLUMN_RIDER_ID => $this->rider->id,
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Action: Complete final destination
        $response = actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.trips.requests.completed', $tripRequest));

        // Assert: Trip should be completed
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'next_action' => null,
                    'trip_completed' => true,
                ],
            ]);

        assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);
    });
});
