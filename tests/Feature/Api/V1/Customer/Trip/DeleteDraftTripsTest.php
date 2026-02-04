<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Models\TripRequestStatusLog;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
    $this->otherCustomer = Customer::factory()->create();
    $this->headers = [
        'Authorization' => 'Bearer ' . $this->customer->createToken('test')->plainTextToken,
    ];

    // Helper function to create a trip
    $this->createTrip = function (array $overrides = []) {
        return Trip::create(array_merge([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        ], $overrides));
    };

    // Helper function to make store trip API request
    $this->storeTripRequest = function () {
        return postJson(route('v1.customers.trips.store'), [
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'origin_location_title' => 'Kuwait City',
            'origin_location_sub_title' => 'Kuwait',
            'origin_latitude' => 29.3759,
            'origin_longitude' => 47.9774,
            'destination_location_title' => 'Salmiya',
            'destination_location_sub_title' => 'Hawalli',
            'destination_latitude' => 29.2941,
            'destination_longitude' => 48.0045,
        ], $this->headers);
    };
});

test('creating new trip deletes all draft trips for customer', function () {
    // Create 2 draft trips for the customer
    $draftTrip1 = ($this->createTrip)();
    $draftTrip2 = ($this->createTrip)();

    // Create draft trip for another customer (should not be deleted)
    $otherDraftTrip = ($this->createTrip)([
        Trip::COLUMN_CUSTOMER_ID => $this->otherCustomer->{Customer::COLUMN_ID},
    ]);

    // Create a new trip via API
    $response = ($this->storeTripRequest)();

    $response->assertOk();

    // Assert customer's draft trips are soft deleted
    expect(Trip::find($draftTrip1->{Trip::COLUMN_ID}))->toBeNull()
        ->and(Trip::find($draftTrip2->{Trip::COLUMN_ID}))->toBeNull()
        ->and(Trip::withTrashed()->find($draftTrip1->{Trip::COLUMN_ID})->trashed())->toBeTrue()
        ->and(Trip::withTrashed()->find($draftTrip2->{Trip::COLUMN_ID})->trashed())->toBeTrue()
        ->and(Trip::find($otherDraftTrip->{Trip::COLUMN_ID}))->not->toBeNull();

    // Verify they exist in trash

    // Assert other customer's draft trip is NOT deleted
});

test('deleting draft trips does not affect completed or cancelled trips', function () {
    // Create draft trip
    $draftTrip1 = ($this->createTrip)();
    $draftTrip2 = ($this->createTrip)();

    // Create completed and cancelled trips (not active trips that would block creation)
    $completedTrip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED]);
    $cancelledTrip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER]);

    // Create a new trip via API (should delete only draft trips)
    ($this->storeTripRequest)();

    // Assert draft trips are soft deleted
    expect(Trip::find($draftTrip1->{Trip::COLUMN_ID}))->toBeNull()
        ->and(Trip::find($draftTrip2->{Trip::COLUMN_ID}))->toBeNull()
        ->and(Trip::withTrashed()->find($draftTrip1->{Trip::COLUMN_ID})->trashed())->toBeTrue()
        ->and(Trip::withTrashed()->find($draftTrip2->{Trip::COLUMN_ID})->trashed())->toBeTrue()
        ->and(Trip::find($completedTrip->{Trip::COLUMN_ID}))->not->toBeNull()
        ->and(Trip::find($cancelledTrip->{Trip::COLUMN_ID}))->not->toBeNull();

    // Assert completed and cancelled trips are NOT deleted
});

test('new trip is created successfully after deleting draft trips', function () {
    // Create multiple draft trips
    for ($i = 0; $i < 3; $i++) {
        ($this->createTrip)();
    }

    // Create a new trip via API
    $response = ($this->storeTripRequest)();

    $response->assertOk();

    // Assert only 1 draft trip exists (the newly created one)
    $draftTripsCount = Trip::query()
        ->where(Trip::COLUMN_CUSTOMER_ID, $this->customer->{Customer::COLUMN_ID})
        ->where(Trip::COLUMN_STATUS, TripStatusEnum::DRAFT)
        ->count();

    expect($draftTripsCount)->toBe(1);

    // Assert the new trip is created with DRAFT status
    $newTrip = Trip::query()
        ->where(Trip::COLUMN_CUSTOMER_ID, $this->customer->{Customer::COLUMN_ID})
        ->latest()
        ->first();

    expect($newTrip)->not->toBeNull()
        ->and($newTrip->{Trip::COLUMN_STATUS})->toBe(TripStatusEnum::DRAFT);
});
