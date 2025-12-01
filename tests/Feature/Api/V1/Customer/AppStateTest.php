<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Trip;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
});

test('customer can get app state when no active trip exists', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'NO_TRIP',
            ],
        ]);
});

test('customer can get app state when has active trip', function () {
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'HAS_ACTIVE_TRIP',
            ],
        ]);
});

test('customer app state ignores completed trips', function () {
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'NO_TRIP',
            ],
        ]);
});

test('customer app state ignores cancelled trips', function () {
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'NO_TRIP',
            ],
        ]);
});

test('unauthenticated customer cannot get app state', function () {
    $response = getJson(route('v1.customers.app-state'));

    $response->assertUnauthorized();
});
