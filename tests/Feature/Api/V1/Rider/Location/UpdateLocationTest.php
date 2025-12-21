<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Company;
use App\Models\Rider;

function apiUrl(string $path): string
{
    return 'http://api.localhost' . $path;
}

beforeEach(function () {
    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    $this->rider = Rider::query()->create([
        Rider::COLUMN_FULL_NAME => 'Test Rider',
        Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
        Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Rider::COLUMN_COMPANY_ID => $company->id,
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);

    $this->token = $this->rider->createToken('test')->plainTextToken;
    $this->headers = [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'application/json',
    ];
});

test('it can update rider location with valid coordinates', function () {
    $response = $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    $response->assertOk();

    // Verify database was updated
    $this->rider->refresh();
    expect((float) $this->rider->{Rider::COLUMN_LATITUDE})->toBe(29.3759)
        ->and((float) $this->rider->{Rider::COLUMN_LONGITUDE})->toBe(47.9774)
        ->and($this->rider->{Rider::COLUMN_LAST_LOCATION_UPDATE})->not->toBeNull();
});

test('it requires authentication', function () {
    $response = $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ]);

    $response->assertUnauthorized();
});

test('it updates location timestamp on each request', function () {
    // First update
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    $this->rider->refresh();
    $firstTimestamp = $this->rider->{Rider::COLUMN_LAST_LOCATION_UPDATE};

    // Wait a moment
    sleep(1);

    // Second update
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3860,
        'longitude' => 47.9880,
    ], $this->headers);

    $this->rider->refresh();
    $secondTimestamp = $this->rider->{Rider::COLUMN_LAST_LOCATION_UPDATE};

    expect($secondTimestamp)->toBeGreaterThan($firstTimestamp);
});

test('it can handle decimal precision in coordinates', function () {
    $response = $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.37594567,
        'longitude' => 47.97745678,
    ], $this->headers);

    $response->assertOk();

    $this->rider->refresh();
    expect((float) $this->rider->{Rider::COLUMN_LATITUDE})->toBe(29.37594567)
        ->and((float) $this->rider->{Rider::COLUMN_LONGITUDE})->toBe(47.97745678);
});

test('it broadcasts location update to customer when rider has active trip', function () {
    Event::fake([App\Events\Socket\Customer\RiderLocationUpdatedEvent::class]);

    // Create a customer
    $customer = \App\Models\Customer::factory()->create();

    // Create an active trip (ACCEPTED_RIDER status)
    $trip = \App\Models\Trip::create([
        \App\Models\Trip::COLUMN_CUSTOMER_ID => $customer->id,
        \App\Models\Trip::COLUMN_RIDER_ID => $this->rider->id,
        \App\Models\Trip::COLUMN_TRIP_TYPE_ID => \App\Enums\Trip\TripTypeEnum::RIDE_NOW,
        \App\Models\Trip::COLUMN_VEHICLE_TYPE_ID => \App\Enums\Trip\TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        \App\Models\Trip::COLUMN_CURRENCY => \App\Enums\Currency\CurrencyEnum::KWD,
        \App\Models\Trip::COLUMN_STATUS => \App\Enums\Trip\TripStatusEnum::ACCEPTED_RIDER,
        \App\Models\Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    // Update location
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    // Assert event was dispatched with correct data
    Event::assertDispatched(
        App\Events\Socket\Customer\RiderLocationUpdatedEvent::class,
        fn ($event) => $event->customerId === $customer->id
            && $event->tripId === $trip->id
            && $event->riderId === $this->rider->id
            && $event->latitude === 29.3759
            && $event->longitude === 47.9774
    );
});

test('it broadcasts location update when trip is IN_PROGRESS status', function () {
    Event::fake([App\Events\Socket\Customer\RiderLocationUpdatedEvent::class]);

    // Create a customer
    $customer = \App\Models\Customer::factory()->create();

    // Create a trip with IN_PROGRESS status
    \App\Models\Trip::create([
        \App\Models\Trip::COLUMN_CUSTOMER_ID => $customer->id,
        \App\Models\Trip::COLUMN_RIDER_ID => $this->rider->id,
        \App\Models\Trip::COLUMN_TRIP_TYPE_ID => \App\Enums\Trip\TripTypeEnum::RIDE_NOW,
        \App\Models\Trip::COLUMN_VEHICLE_TYPE_ID => \App\Enums\Trip\TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        \App\Models\Trip::COLUMN_CURRENCY => \App\Enums\Currency\CurrencyEnum::KWD,
        \App\Models\Trip::COLUMN_STATUS => \App\Enums\Trip\TripStatusEnum::IN_PROGRESS,
        \App\Models\Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    // Update location
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    // Assert event was dispatched
    Event::assertDispatched(App\Events\Socket\Customer\RiderLocationUpdatedEvent::class);
});

test('it does NOT broadcast location update when rider has no active trip', function () {
    Event::fake([App\Events\Socket\Customer\RiderLocationUpdatedEvent::class]);

    // Update location (no active trip)
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    // Assert event was NOT dispatched
    Event::assertNotDispatched(App\Events\Socket\Customer\RiderLocationUpdatedEvent::class);
});

test('it does NOT broadcast location update when trip is completed', function () {
    Event::fake([App\Events\Socket\Customer\RiderLocationUpdatedEvent::class]);

    // Create a customer
    $customer = \App\Models\Customer::factory()->create();

    // Create a completed trip
    \App\Models\Trip::create([
        \App\Models\Trip::COLUMN_CUSTOMER_ID => $customer->id,
        \App\Models\Trip::COLUMN_RIDER_ID => $this->rider->id,
        \App\Models\Trip::COLUMN_TRIP_TYPE_ID => \App\Enums\Trip\TripTypeEnum::RIDE_NOW,
        \App\Models\Trip::COLUMN_VEHICLE_TYPE_ID => \App\Enums\Trip\TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        \App\Models\Trip::COLUMN_CURRENCY => \App\Enums\Currency\CurrencyEnum::KWD,
        \App\Models\Trip::COLUMN_STATUS => \App\Enums\Trip\TripStatusEnum::COMPLETED,
        \App\Models\Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    // Update location
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    // Assert event was NOT dispatched
    Event::assertNotDispatched(App\Events\Socket\Customer\RiderLocationUpdatedEvent::class);
});

test('it does NOT broadcast location update when trip is pending rider', function () {
    Event::fake([App\Events\Socket\Customer\RiderLocationUpdatedEvent::class]);

    // Create a customer
    $customer = \App\Models\Customer::factory()->create();

    // Create a pending trip (no rider assigned yet)
    \App\Models\Trip::create([
        \App\Models\Trip::COLUMN_CUSTOMER_ID => $customer->id,
        \App\Models\Trip::COLUMN_RIDER_ID => $this->rider->id,
        \App\Models\Trip::COLUMN_TRIP_TYPE_ID => \App\Enums\Trip\TripTypeEnum::RIDE_NOW,
        \App\Models\Trip::COLUMN_VEHICLE_TYPE_ID => \App\Enums\Trip\TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        \App\Models\Trip::COLUMN_CURRENCY => \App\Enums\Currency\CurrencyEnum::KWD,
        \App\Models\Trip::COLUMN_STATUS => \App\Enums\Trip\TripStatusEnum::PENDING_RIDER,
        \App\Models\Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    // Update location
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    // Assert event was NOT dispatched (PENDING_RIDER is not in canGetRiderLocation)
    Event::assertNotDispatched(App\Events\Socket\Customer\RiderLocationUpdatedEvent::class);
});
