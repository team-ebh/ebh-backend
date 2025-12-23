<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Trip;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
    $this->otherCustomer = Customer::factory()->create();

    $this->trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
        Trip::COLUMN_TOTAL_PRICE => 10.5,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
    ]);

    $this->payment = Payment::create([
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-123456789',
        Payment::COLUMN_CUSTOMER_ID => $this->customer->id,
        Payment::COLUMN_TRIP_ID => $this->trip->id,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PAID,
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_GATEWAY_REFERENCE_ID => 'ref-123',
        Payment::COLUMN_AMOUNT => 10.5,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_ATTEMPT => 1,
    ]);
});

test('customer can check their own payment status', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.check-status', [
            'payment' => $this->payment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'payment_method' => PaymentMethodEnum::KNET->getLabel(),
                'payment_number' => 'PAY-123456789',
                'payment_status' => PaymentStatusEnum::PAID->getFrontendLabel(),
                'payment' => [
                    'price' => 10.5,
                    'currency' => CurrencyEnum::KWD->getLabel(),
                ],
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'order_id',
                'date_time',
                'payment_method',
                'payment_number',
                'payment_status',
                'payment' => [
                    'price',
                    'currency',
                ],
            ],
        ]);
});

test('customer cannot check another customer payment status', function () {
    $response = actingAs($this->otherCustomer, 'customer')
        ->getJson(route('v1.customers.payments.check-status', [
            'payment' => $this->payment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertForbidden();
});

test('unauthenticated customer cannot check payment status', function () {
    $response = getJson(route('v1.customers.payments.check-status', [
        'payment' => $this->payment->{Payment::COLUMN_PAYMENT_NUMBER},
    ]));

    $response->assertUnauthorized();
});

test('returns not found for non-existent payment number', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.check-status', [
            'payment' => 'INVALID-PAYMENT-NUMBER',
        ]));

    $response->assertNotFound();
});

test('returns correct payment status for pending payment', function () {
    $pendingPayment = Payment::create([
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-PENDING-001',
        Payment::COLUMN_CUSTOMER_ID => $this->customer->id,
        Payment::COLUMN_TRIP_ID => $this->trip->id,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PENDING,
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_GATEWAY_REFERENCE_ID => 'ref-pending',
        Payment::COLUMN_AMOUNT => 15.75,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_ATTEMPT => 1,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.check-status', [
            'payment' => $pendingPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'payment_status' => PaymentStatusEnum::PENDING->getFrontendLabel(),
                'payment' => [
                    'price' => 15.75,
                    'currency' => CurrencyEnum::KWD->getLabel(),
                ],
            ],
        ]);
});

test('returns correct payment status for failed payment', function () {
    $failedPayment = Payment::create([
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-FAILED-001',
        Payment::COLUMN_CUSTOMER_ID => $this->customer->id,
        Payment::COLUMN_TRIP_ID => $this->trip->id,
        Payment::COLUMN_STATUS => PaymentStatusEnum::FAILED,
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_GATEWAY_REFERENCE_ID => 'ref-failed',
        Payment::COLUMN_AMOUNT => 20.0,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_ATTEMPT => 2,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.check-status', [
            'payment' => $failedPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'payment_status' => PaymentStatusEnum::FAILED->getFrontendLabel(),
            ],
        ]);
});
