<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Services\Trip\TripLocationFormatterService;

beforeEach(function () {
    $this->service = new TripLocationFormatterService();
    $this->customer = Customer::factory()->create();
});

test('only first unfinished segment should be active in multi-location trip', function () {
    // Create trip with 3 locations: A (origin) → B (destination) → C (destination)
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.0,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
    ]);

    $locationA = TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING,
        TripLocation::COLUMN_LOCATION_TITLE => 'Location A',
        TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Origin',
        TripLocation::COLUMN_LATITUDE => 29.0,
        TripLocation::COLUMN_LONGITUDE => 48.0,
        TripLocation::COLUMN_SEQUENCE => 1,
    ]);

    $locationB = TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING,
        TripLocation::COLUMN_LOCATION_TITLE => 'Location B',
        TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Waypoint',
        TripLocation::COLUMN_LATITUDE => 29.1,
        TripLocation::COLUMN_LONGITUDE => 48.1,
        TripLocation::COLUMN_SEQUENCE => 2,
    ]);

    $locationC = TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING,
        TripLocation::COLUMN_LOCATION_TITLE => 'Location C',
        TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Final Destination',
        TripLocation::COLUMN_LATITUDE => 29.2,
        TripLocation::COLUMN_LONGITUDE => 48.2,
        TripLocation::COLUMN_SEQUENCE => 3,
    ]);

    $trip->load('locations');

    // Format locations
    $formatted = $this->service->prepareFormattedLocations($trip);

    // Expected: 2 segments (A→B and B→C)
    expect($formatted)->toHaveCount(2);

    // Segment A→B should be ACTIVE (first unfinished segment)
    expect($formatted[0]['is_active'])->toBe(true)
        ->and($formatted[0]['from']['location_title'])->toBe('Location A')
        ->and($formatted[0]['to']['location_title'])->toBe('Location B');

    // Segment B→C should be INACTIVE (not the first unfinished segment)
    expect($formatted[1]['is_active'])->toBe(false)
        ->and($formatted[1]['from']['location_title'])->toBe('Location B')
        ->and($formatted[1]['to']['location_title'])->toBe('Location C');
});

test('active segment changes when first destination is finished', function () {
    // Create trip with 3 locations: A (picked_up) → B (dropped_off) → C (pending)
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.0,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS,
    ]);

    TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PICKED_UP, // Finished
        TripLocation::COLUMN_SEQUENCE => 1,
        TripLocation::COLUMN_LOCATION_TITLE => 'Location A',
        TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Origin',
        TripLocation::COLUMN_LATITUDE => 29.0,
        TripLocation::COLUMN_LONGITUDE => 48.0,
    ]);

    TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::DROPPED_OFF, // Finished
        TripLocation::COLUMN_SEQUENCE => 2,
        TripLocation::COLUMN_LOCATION_TITLE => 'Location B',
        TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Waypoint',
        TripLocation::COLUMN_LATITUDE => 29.1,
        TripLocation::COLUMN_LONGITUDE => 48.1,
    ]);

    TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING, // Not finished
        TripLocation::COLUMN_SEQUENCE => 3,
        TripLocation::COLUMN_LOCATION_TITLE => 'Location C',
        TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Final Destination',
        TripLocation::COLUMN_LATITUDE => 29.2,
        TripLocation::COLUMN_LONGITUDE => 48.2,
    ]);

    $trip->load('locations');

    // Format locations
    $formatted = $this->service->prepareFormattedLocations($trip);

    // Expected: 2 segments
    expect($formatted)->toHaveCount(2);

    // Segment A→B should be INACTIVE (B is finished)
    expect($formatted[0]['is_active'])->toBe(false);

    // Segment B→C should be ACTIVE (C is not finished and it's the first unfinished)
    expect($formatted[1]['is_active'])->toBe(true);
});
