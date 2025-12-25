<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\TripLocation;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

describe('Change Ride Type API - Update Ride Type and Recalculate Pricing', function () {
    beforeEach(function () {
        // Authenticate a customer
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');

        // Create a draft trip with origin and destination locations
        $this->trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 2,
            Trip::COLUMN_ACCESSIBILITY_PRICE => null,
            Trip::COLUMN_WAITING_PRICE => null,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
        ]);

        // Create origin location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $this->trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin Location',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Origin Sublocation',
            TripLocation::COLUMN_LATITUDE => 29.37694,
            TripLocation::COLUMN_LONGITUDE => 47.98306,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Create destination location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $this->trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination Location',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Destination Sublocation',
            TripLocation::COLUMN_LATITUDE => 29.22667,
            TripLocation::COLUMN_LONGITUDE => 47.96889,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);
    });

    it('updates ride type to ROUND_TRIP with scheduled time and recalculates price', function () {
        $returnTime = now()->addHours(2)->timestamp;

        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
            'destination_location_title' => 'New Destination',
            'destination_location_sub_title' => 'New Sublocation',
            'destination_latitude' => 29.30000,
            'destination_longitude' => 48.00000,
            'return_time' => $returnTime,
        ])->assertStatus(200);

        // Verify response structure
        $response->assertJsonStructure([
            'data' => [
                'price_breakdown',
                'price_estimation',
            ],
        ]);

        // Verify trip WAS modified
        $this->trip->refresh();
        expect($this->trip->{Trip::COLUMN_RIDE_TYPE})->toBe(RideTypeEnum::ROUND_TRIP);
        expect($this->trip->{Trip::COLUMN_SCHEDULED_TIME}->timestamp)->toBe($returnTime);
        expect($this->trip->{Trip::COLUMN_TOTAL_PRICE})->toBeGreaterThan(0);
    });

    it('updates ride type to ROUND_TRIP_WAIT and recalculates price', function () {
        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP_WAIT->value,
            'destination_location_title' => 'Wait Destination',
            'destination_location_sub_title' => 'Wait Sublocation',
            'destination_latitude' => 29.35000,
            'destination_longitude' => 48.10000,
        ])->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'price_breakdown',
                'price_estimation',
                'waiting_time_config',
            ],
        ]);

        // Verify trip WAS modified
        $this->trip->refresh();
        expect($this->trip->{Trip::COLUMN_RIDE_TYPE})->toBe(RideTypeEnum::ROUND_TRIP_WAIT);
        expect($this->trip->{Trip::COLUMN_SCHEDULED_TIME})->toBeNull();
        expect($this->trip->{Trip::COLUMN_TOTAL_PRICE})->toBeGreaterThan(0);
    });

    it('validates that destination is required for ROUND_TRIP', function () {
        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
            'return_time' => now()->addHours(2)->timestamp,
        ])->assertStatus(422);
    });

    it('validates that return_time is required for ROUND_TRIP', function () {
        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
            'destination_location_title' => 'Destination',
            'destination_location_sub_title' => 'Sublocation',
            'destination_latitude' => 29.30000,
            'destination_longitude' => 48.00000,
        ])->assertStatus(422);
    });

    it('validates that return_time must be in the future for ROUND_TRIP', function () {
        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
            'destination_location_title' => 'Destination',
            'destination_location_sub_title' => 'Sublocation',
            'destination_latitude' => 29.30000,
            'destination_longitude' => 48.00000,
            'return_time' => now()->subHours(1)->timestamp,
        ])->assertStatus(422);
    });

    it('updates ride type to ONE_WAY without destination or return_time', function () {
        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ONE_WAY->value,
        ])->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'price_breakdown',
                'price_estimation',
            ],
        ]);

        // Verify trip ride type remains ONE_WAY
        $this->trip->refresh();
        expect($this->trip->{Trip::COLUMN_RIDE_TYPE})->toBe(RideTypeEnum::ONE_WAY);
        expect($this->trip->{Trip::COLUMN_SCHEDULED_TIME})->toBeNull();
    });

    it('cannot change ride type for non-draft trip', function () {
        $this->trip->update([Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER]);

        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
            'destination_location_title' => 'Destination',
            'destination_location_sub_title' => 'Sublocation',
            'destination_latitude' => 29.30000,
            'destination_longitude' => 48.00000,
            'return_time' => now()->addHours(2)->timestamp,
        ])->assertStatus(406);
    });
});
