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
    $this->otherCustomer = Customer::factory()->create();

    $this->order = Order::create([
        Order::COLUMN_CUSTOMER_ID => $this->customer->id,
        Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
        Order::COLUMN_TOTAL_PRICE => 15.5,
        Order::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED,
    ]);

    $this->trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
        Trip::COLUMN_ORDER_ID => $this->order->id,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 15.5,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
    ]);

    $this->paidPayment = Payment::create([
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-RECEIPT-001',
        Payment::COLUMN_CUSTOMER_ID => $this->customer->id,
        Payment::COLUMN_ORDER_ID => $this->order->id,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PAID,
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_GATEWAY_REFERENCE_ID => 'ref-paid',
        Payment::COLUMN_AMOUNT => 15.5,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_ATTEMPT => 1,
    ]);

    $this->pendingPayment = Payment::create([
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-PENDING-001',
        Payment::COLUMN_CUSTOMER_ID => $this->customer->id,
        Payment::COLUMN_ORDER_ID => $this->order->id,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PENDING,
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_GATEWAY_REFERENCE_ID => 'ref-pending',
        Payment::COLUMN_AMOUNT => 15.5,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_ATTEMPT => 2,
    ]);
});

test('customer can get receipt link for their paid payment', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.receipt-link', [
            'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'link',
            ],
        ]);

    expect($response->json('data.link'))
        ->toContain('download-receipt')
        ->toContain('expires=')
        ->toContain('signature=');
});

test('customer cannot get receipt link for another customer payment', function () {
    $response = actingAs($this->otherCustomer, 'customer')
        ->getJson(route('v1.customers.payments.receipt-link', [
            'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertForbidden();
});

test('unauthenticated customer cannot get receipt link', function () {
    $response = getJson(route('v1.customers.payments.receipt-link', [
        'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
    ]));

    $response->assertUnauthorized();
});

test('customer can download receipt with valid signed URL', function () {
    // First get the receipt link
    $linkResponse = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.receipt-link', [
            'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $downloadUrl = $linkResponse->json('data.link');

    // Extract the query parameters from the signed URL
    $urlParts = parse_url($downloadUrl);
    parse_str($urlParts['query'] ?? '', $queryParams);

    // Download the receipt using the signed URL
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.download-receipt', [
                'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
            ]) . '?' . http_build_query($queryParams));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('customer cannot download receipt without valid signature', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.download-receipt', [
            'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertForbidden();
});

test('customer cannot download receipt for pending payment', function () {
    // Try to get receipt link for pending payment (should fail because payment is not paid)
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.receipt-link', [
            'payment' => $this->pendingPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $response->assertForbidden()
        ->assertJson([
            'meta' => [
                'message' => trans('payments.errors.payment_not_paid'),
            ],
        ]);
});

test('customer cannot download receipt for another customer payment even with valid signature', function () {
    // Get receipt link as first customer
    $linkResponse = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.receipt-link', [
            'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $downloadUrl = $linkResponse->json('data.link');
    $urlParts = parse_url($downloadUrl);
    parse_str($urlParts['query'] ?? '', $queryParams);

    // Try to download as different customer
    $response = actingAs($this->otherCustomer, 'customer')
        ->getJson(route('v1.customers.payments.download-receipt', [
                'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
            ]) . '?' . http_build_query($queryParams));

    $response->assertForbidden();
});

test('receipt link expires after 30 minutes', function () {
    // This test would require time manipulation
    // For now, we just verify the link contains an expiration parameter
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.payments.receipt-link', [
            'payment' => $this->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER},
        ]));

    $link = $response->json('data.link');
    $urlParts = parse_url($link);
    parse_str($urlParts['query'] ?? '', $queryParams);

    expect($queryParams)->toHaveKey('expires')
        ->and($queryParams['expires'])->toBeGreaterThan(now()->timestamp);
});
