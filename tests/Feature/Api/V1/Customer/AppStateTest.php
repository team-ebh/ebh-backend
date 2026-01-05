<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
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
    $order = Order::query()->create([
        Order::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET->value,
        Order::COLUMN_TOTAL_PRICE => 5.000,
        Order::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED,
    ]);

    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_ORDER_ID => $order->{Order::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    // Create paid payment for the completed trip
    Payment::query()->create([
        Payment::COLUMN_ORDER_ID => $order->{Order::COLUMN_ID},
        Payment::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Payment::COLUMN_AMOUNT => 5.000,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS->value,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PAID->value,
        Payment::COLUMN_PAYMENT_NUMBER => generatePaymentNumber(),
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

test('customer can get app state when has scheduled trip', function () {
    // Create a scheduled trip (DRAFT with SCHEDULED type)
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_SCHEDULED_TIME => now()->addHour(),
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.app-state'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'HAS_SCHEDULED_TRIP',
            ],
        ]);
});

test('customer app state prioritizes active trip over scheduled trip', function () {
    // Create a scheduled trip
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_SCHEDULED_TIME => now()->addHour(),
    ]);

    // Create an active trip
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

    // Active trip should take priority
    $response->assertOk()
        ->assertJson([
            'data' => [
                'state' => 'HAS_ACTIVE_TRIP',
            ],
        ]);
});

test('unauthenticated customer cannot get app state', function () {
    $response = getJson(route('v1.customers.app-state'));

    $response->assertUnauthorized();
});
