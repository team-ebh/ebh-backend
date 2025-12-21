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
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

describe('Estimated Arrival Time Consistency Integration Tests', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        $this->rider = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE->value,
            Rider::COLUMN_LATITUDE => 29.3800,  // Starting position (near origin)
            Rider::COLUMN_LONGITUDE => 47.9800,
        ]);

        // Helper to get customer arrived_time from Get Trip Status API
        $this->getCustomerArrivedTime = function (Trip $trip): ?int {
            $response = actingAs($this->customer, 'customer')
                ->getJson(route('v1.customers.trips.status', $trip));

            $response->assertOk();

            return $response->json('data.arrived_time');
        };

        // Helper to get rider arrived_time from Get Estimated Arrival Time API
        $this->getRiderArrivedTime = function (TripRequest $tripRequest): ?int {
            $response = actingAs($this->rider, 'rider')
                ->getJson(route('v1.riders.trips.requests.estimated_arrival_time', $tripRequest));

            if ($response->status() !== 200) {
                return null;
            }

            return $response->json('data.estimated_arrival_seconds');
        };

        // Helper to update rider location
        $this->updateRiderLocation = function (float $latitude, float $longitude): void {
            actingAs($this->rider, 'rider')
                ->postJson(route('v1.riders.location.update'), [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ])
                ->assertOk();

            // Refresh rider to get updated location
            $this->rider->refresh();
        };
    });

    test('rider and customer get same arrived_time when trip is accepted', function () {
        // Create trip with origin and destination
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        // Create origin location (close to rider)
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,  // ~500m from rider
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Create destination location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        // Create accepted trip request
        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Get arrived_time from both APIs
        $customerArrivedTime = ($this->getCustomerArrivedTime)($trip);
        $riderArrivedTime = ($this->getRiderArrivedTime)($tripRequest);

        // Both should be integers (not null)
        expect($customerArrivedTime)->toBeInt()
            ->and($riderArrivedTime)->toBeInt()
            ->and(abs($customerArrivedTime - $riderArrivedTime))
            ->toBeLessThanOrEqual(1)
            ->and($customerArrivedTime)->toBeGreaterThan(0)
            ->and($riderArrivedTime)->toBeGreaterThan(0);

        // Both should be the same (within 1 second tolerance for calculation differences)
    });

    test('both APIs calculate new arrival time when rider location changes before pickup', function () {
        // Create trip with ACCEPTED_RIDER status
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        // Create origin location
        $originLocation = TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Get initial arrival times
        $initialCustomerTime = ($this->getCustomerArrivedTime)($trip);
        $initialRiderTime = ($this->getRiderArrivedTime)($tripRequest);

        expect($initialCustomerTime)->toBeInt()
            ->and($initialRiderTime)->toBeInt();

        // Update rider location - move closer to origin
        ($this->updateRiderLocation)(29.3770, 47.9780);

        // Get new arrival times
        $newCustomerTime = ($this->getCustomerArrivedTime)($trip);
        $newRiderTime = ($this->getRiderArrivedTime)($tripRequest);

        // Times should be updated (shorter since rider is closer)
        expect($newCustomerTime)
            ->toBeInt()
            ->toBeLessThan($initialCustomerTime)
            ->and($newRiderTime)
            ->toBeInt()
            ->toBeLessThan($initialRiderTime)
            ->and(abs($newCustomerTime - $newRiderTime))->toBeLessThanOrEqual(1);

        // Move rider farther from origin
        ($this->updateRiderLocation)(29.3850, 47.9850);

        $fartherCustomerTime = ($this->getCustomerArrivedTime)($trip);
        $fartherRiderTime = ($this->getRiderArrivedTime)($tripRequest);

        // Times should increase (longer since rider is farther)
        expect($fartherCustomerTime)->toBeGreaterThan($newCustomerTime)
            ->and($fartherRiderTime)->toBeGreaterThan($newRiderTime)
            ->and(abs($fartherCustomerTime - $fartherRiderTime))->toBeLessThanOrEqual(1);
    });

    test('arrival time to destination is consistent after pickup', function () {
        // Create trip with IN_PROGRESS status (already picked up)
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value,
        ]);

        // Origin already picked up
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Destination pending
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,  // Destination location
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Set rider position (between origin and destination)
        ($this->updateRiderLocation)(29.3830, 47.9850);

        // Get arrival times to destination
        $customerArrivedTime = ($this->getCustomerArrivedTime)($trip);
        $riderArrivedTime = ($this->getRiderArrivedTime)($tripRequest);

        // Both should calculate arrival to destination
        expect($customerArrivedTime)->toBeInt()->toBeGreaterThan(0)
            ->and($riderArrivedTime)->toBeInt()->toBeGreaterThan(0)
            ->and(abs($customerArrivedTime - $riderArrivedTime))->toBeLessThanOrEqual(1);
    });

    test('arrival time to destination updates correctly when rider moves during trip', function () {
        // Create trip IN_PROGRESS
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        $destinationLocation = TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_LATITUDE => 29.3900,
            TripLocation::COLUMN_LONGITUDE => 47.9900,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Rider starts far from destination
        ($this->updateRiderLocation)(29.3759, 47.9774);  // At origin

        $initialCustomerTime = ($this->getCustomerArrivedTime)($trip);
        $initialRiderTime = ($this->getRiderArrivedTime)($tripRequest);

        expect($initialCustomerTime)->toBeInt()->toBeGreaterThan(0)
            ->and($initialRiderTime)->toBeInt()->toBeGreaterThan(0)
            ->and(abs($initialCustomerTime - $initialRiderTime))->toBeLessThanOrEqual(1);

        // Rider moves closer to destination (halfway)
        ($this->updateRiderLocation)(29.3830, 47.9850);

        $halfwayCustomerTime = ($this->getCustomerArrivedTime)($trip);
        $halfwayRiderTime = ($this->getRiderArrivedTime)($tripRequest);

        // Time should decrease (rider is closer)
        expect($halfwayCustomerTime)->toBeLessThan($initialCustomerTime)
            ->and($halfwayRiderTime)->toBeLessThan($initialRiderTime)
            ->and(abs($halfwayCustomerTime - $halfwayRiderTime))->toBeLessThanOrEqual(1);

        // Rider moves very close to destination
        ($this->updateRiderLocation)(29.3895, 47.9895);

        $nearCustomerTime = ($this->getCustomerArrivedTime)($trip);
        $nearRiderTime = ($this->getRiderArrivedTime)($tripRequest);

        // Time should be very short now
        expect($nearCustomerTime)->toBeLessThan($halfwayCustomerTime)->toBeGreaterThan(0)
            ->and($nearRiderTime)->toBeLessThan($halfwayRiderTime)->toBeGreaterThan(0)
            ->and(abs($nearCustomerTime - $nearRiderTime))->toBeLessThanOrEqual(1);
    });

    test('arrived_time is null when trip is in PENDING_RIDER status', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER->value,  // Not yet accepted
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        $customerArrivedTime = ($this->getCustomerArrivedTime)($trip);

        // Should be null since trip is not accepted yet
        expect($customerArrivedTime)->toBeNull();
    });

    test('trip status API throws exception when trip is completed', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,  // Trip finished
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Get Trip Status API should return error for completed trips
        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trips.status', $trip));

        // Should return 406 (TripStatusCannotBeCheckedException)
        $response->assertStatus(406);
    });

    test('arrival time calculation works with rider at moderately far distance', function () {
        // Use the main rider but update location to be farther
        ($this->updateRiderLocation)(29.4100, 48.0200);  // About 5km from origin

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 5000,  // 5km
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 600,  // 10 minutes
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        $customerArrivedTime = ($this->getCustomerArrivedTime)($trip);
        $riderArrivedTime = ($this->getRiderArrivedTime)($tripRequest);

        // Should calculate arrival time even for moderate distances
        expect($customerArrivedTime)
            ->toBeInt()
            ->toBeGreaterThan(200)
            ->and($riderArrivedTime)
            ->toBeInt()
            ->toBeGreaterThan(200)
            ->and(abs($customerArrivedTime - $riderArrivedTime))->toBeLessThanOrEqual(1);  // Should be more than 200 seconds for 5km
    });

    test('complete trip lifecycle with multiple destination changes', function () {
        // Create trip with multiple destinations
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        // Origin
        $origin = TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // First destination
        $destination1 = TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_LATITUDE => 29.3850,
            TripLocation::COLUMN_LONGITUDE => 47.9850,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        // Second destination
        $destination2 = TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_LATITUDE => 29.3950,
            TripLocation::COLUMN_LONGITUDE => 47.9950,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 3,
        ]);

        $tripRequest = TripRequest::create([
            TripRequest::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripRequest::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
            TripRequest::COLUMN_DISTANCE_METERS => 1000,
            TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
            TripRequest::COLUMN_SENT_AT => now(),
        ]);

        // Phase 1: Rider going to origin
        ($this->updateRiderLocation)(29.3800, 47.9800);

        $timeToOrigin1 = ($this->getCustomerArrivedTime)($trip);
        $timeToOrigin2 = ($this->getRiderArrivedTime)($tripRequest);

        expect($timeToOrigin1)->toBeInt()->toBeGreaterThan(0)
            ->and(abs($timeToOrigin1 - $timeToOrigin2))->toBeLessThanOrEqual(1);

        // Phase 2: Picked up at origin, going to first destination
        $origin->update([TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP->value]);
        $trip->update([Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS->value]);

        ($this->updateRiderLocation)(29.3780, 47.9820);

        $timeToDest1_1 = ($this->getCustomerArrivedTime)($trip);
        $timeToDest1_2 = ($this->getRiderArrivedTime)($tripRequest);

        expect($timeToDest1_1)->toBeInt()->toBeGreaterThan(0)
            ->and(abs($timeToDest1_1 - $timeToDest1_2))->toBeLessThanOrEqual(1);

        // Phase 3: Arrived at first destination
        $destination1->update([TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED->value]);

        ($this->updateRiderLocation)(29.3860, 47.9860);

        $timeToDest2_1 = ($this->getCustomerArrivedTime)($trip);
        $timeToDest2_2 = ($this->getRiderArrivedTime)($tripRequest);

        // Now calculating time to second destination
        expect($timeToDest2_1)->toBeInt()->toBeGreaterThan(0)
            ->and(abs($timeToDest2_1 - $timeToDest2_2))->toBeLessThanOrEqual(1);

        // Phase 4: Moving closer to second destination
        ($this->updateRiderLocation)(29.3920, 47.9920);

        $timeToDest2_3 = ($this->getCustomerArrivedTime)($trip);
        $timeToDest2_4 = ($this->getRiderArrivedTime)($tripRequest);

        // Time should be less (rider is closer)
        expect($timeToDest2_3)->toBeLessThan($timeToDest2_1)
            ->and($timeToDest2_4)->toBeLessThan($timeToDest2_2)
            ->and(abs($timeToDest2_3 - $timeToDest2_4))->toBeLessThanOrEqual(1);
    });
});
