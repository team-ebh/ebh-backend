<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Filament\Resources\TripResource;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripStatusLog;

use function Pest\Livewire\livewire;

beforeEach(function () {
    adminPanelLogin();
});

test('it can render view trip page', function () {
    $customer = Customer::factory()->create();
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 10.00,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::CASH->value,
        Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
    ]);

    livewire(TripResource\Pages\ViewTrip::class, [
        'record' => $trip->id,
    ])
        ->assertSuccessful();
});

test('it can render view trip page with status logs', function () {
    $customer = Customer::factory()->create();
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 10.00,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::CASH->value,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER->value,
    ]);

    // Create status logs
    TripStatusLog::create([
        TripStatusLog::COLUMN_TRIP_ID => $trip->id,
        TripStatusLog::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
    ]);
    TripStatusLog::create([
        TripStatusLog::COLUMN_TRIP_ID => $trip->id,
        TripStatusLog::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER->value,
    ]);

    livewire(TripResource\Pages\ViewTrip::class, [
        'record' => $trip->id,
    ])
        ->assertSuccessful();
});

test('it can render view trip page with locations and location status logs', function () {
    $customer = Customer::factory()->create();
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 10.00,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::CASH->value,
        Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER->value,
    ]);

    // Create locations
    $location = TripLocation::create([
        TripLocation::COLUMN_TRIP_ID => $trip->id,
        TripLocation::COLUMN_LATITUDE => 29.3759,
        TripLocation::COLUMN_LONGITUDE => 47.9774,
        TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
        TripLocation::COLUMN_SEQUENCE => 1,
        TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
    ]);

    // Create status logs for trip
    TripStatusLog::create([
        TripStatusLog::COLUMN_TRIP_ID => $trip->id,
        TripStatusLog::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
    ]);

    livewire(TripResource\Pages\ViewTrip::class, [
        'record' => $trip->id,
    ])
        ->assertSuccessful();
});
