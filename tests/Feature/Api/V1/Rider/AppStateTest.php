<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Rider;
use App\Models\Trip;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);
});

test('rider can get app state when offline', function () {
    $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE]);

    $response = actingAs($this->rider, 'rider')
        ->getJson(route('v1.riders.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'OFFLINE',
            ],
        ]);
});

test('rider can get app state when online and idle', function () {
    $response = actingAs($this->rider, 'rider')
        ->getJson(route('v1.riders.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'ONLINE_IDLE',
            ],
        ]);
});

test('rider can get app state when has active trip', function () {
    Trip::query()->create([
        Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->getJson(route('v1.riders.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'HAS_ACTIVE_TRIP',
            ],
        ]);
});

test('rider app state ignores completed trips', function () {
    Trip::query()->create([
        Trip::COLUMN_RIDER_ID => $this->rider->{Rider::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->getJson(route('v1.riders.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'ONLINE_IDLE',
            ],
        ]);
});

test('unauthenticated rider cannot get app state', function () {
    $response = getJson(route('v1.riders.app-state'));

    $response->assertUnauthorized();
});
