<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
    $this->otherCustomer = Customer::factory()->create();
    $this->rider = Rider::factory()->create();

    $this->createTrip = function (array $overrides = []) {
        return Trip::create(array_merge([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 15.500,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        ], $overrides));
    };

    $this->createOrder = function (array $overrides = []) {
        return Order::create(array_merge([
            Order::COLUMN_CUSTOMER_ID => $this->customer->id,
            Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
            Order::COLUMN_TOTAL_PRICE => 15.500,
            Order::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED,
        ], $overrides));
    };

    $this->createPayment = function (int $orderId, array $overrides = []) {
        return Payment::create(array_merge([
            Payment::COLUMN_PAYMENT_NUMBER => 'PAY-TRIP-' . uniqid(),
            Payment::COLUMN_CUSTOMER_ID => $this->customer->id,
            Payment::COLUMN_ORDER_ID => $orderId,
            Payment::COLUMN_STATUS => PaymentStatusEnum::PAID,
            Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
            Payment::COLUMN_GATEWAY_REFERENCE_ID => 'ref-' . uniqid(),
            Payment::COLUMN_AMOUNT => 15.500,
            Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Payment::COLUMN_ATTEMPT => 1,
        ], $overrides));
    };

    $this->createLocation = function (int $tripId, array $overrides = []) {
        return TripLocation::create(array_merge([
            TripLocation::COLUMN_TRIP_ID => $tripId,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED,
            TripLocation::COLUMN_LOCATION_TITLE => 'Test Location',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
        ], $overrides));
    };
});

describe('Get Trip Receipt Link API', function () {
    test('customer can get receipt link for completed trip with KNET payment', function () {
        $order = ($this->createOrder)();
        $trip = ($this->createTrip)([Trip::COLUMN_ORDER_ID => $order->id]);
        ($this->createPayment)($order->id);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['link'],
            ]);

        $link = $response->json('data.link');
        expect($link)->toContain('/trip-history/')
            ->toContain('/download-receipt')
            ->toContain('signature=');
    });

    test('customer can get receipt link for completed trip with cash payment', function () {
        $order = ($this->createOrder)([Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::CASH]);
        $trip = ($this->createTrip)([Trip::COLUMN_ORDER_ID => $order->id]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['link'],
            ]);
    });

    test('customer can get receipt link for completed trip without order', function () {
        $trip = ($this->createTrip)();
        ($this->createLocation)($trip->id);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['link'],
            ]);
    });

    test('customer cannot get receipt link for non-completed trip', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER]);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertStatus(406)
            ->assertJson([
                'meta' => [
                    'message' => trans('trips.api.exceptions.trip_not_completed'),
                ],
            ]);
    });

    test('customer cannot get receipt link for another customer trip', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_CUSTOMER_ID => $this->otherCustomer->id]);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertStatus(406);
    });

    test('unauthenticated customer cannot get receipt link', function () {
        $trip = ($this->createTrip)();

        $response = getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertUnauthorized();
    });
});

describe('Download Trip Receipt API', function () {
    test('customer can download receipt with valid signed URL for KNET payment trip', function () {
        $order = ($this->createOrder)();
        $trip = ($this->createTrip)([Trip::COLUMN_ORDER_ID => $order->id]);
        ($this->createPayment)($order->id);
        ($this->createLocation)($trip->id);

        // Get the receipt link
        $linkResponse = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $downloadUrl = $linkResponse->json('data.link');
        $urlParts = parse_url($downloadUrl);
        parse_str($urlParts['query'] ?? '', $queryParams);

        // Download the receipt using the signed URL
        $response = getJson(
            route('v1.customers.trip-history.download-receipt', ['trip' => $trip->id])
            . '?' . http_build_query($queryParams)
        );

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    test('customer can download receipt for cash payment trip', function () {
        $order = ($this->createOrder)([Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::CASH]);
        $trip = ($this->createTrip)([Trip::COLUMN_ORDER_ID => $order->id]);
        ($this->createLocation)($trip->id);

        // Get the receipt link
        $linkResponse = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $downloadUrl = $linkResponse->json('data.link');
        $urlParts = parse_url($downloadUrl);
        parse_str($urlParts['query'] ?? '', $queryParams);

        // Download the receipt
        $response = getJson(
            route('v1.customers.trip-history.download-receipt', ['trip' => $trip->id])
            . '?' . http_build_query($queryParams)
        );

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    test('customer can download receipt for trip without payment', function () {
        $trip = ($this->createTrip)();
        ($this->createLocation)($trip->id);

        // Get the receipt link
        $linkResponse = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $downloadUrl = $linkResponse->json('data.link');
        $urlParts = parse_url($downloadUrl);
        parse_str($urlParts['query'] ?? '', $queryParams);

        // Download the receipt
        $response = getJson(
            route('v1.customers.trip-history.download-receipt', ['trip' => $trip->id])
            . '?' . http_build_query($queryParams)
        );

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    test('cannot download receipt without valid signature', function () {
        $trip = ($this->createTrip)();

        $response = getJson(route('v1.customers.trip-history.download-receipt', ['trip' => $trip->id]));

        $response->assertForbidden();
    });

    test('cannot download receipt with invalid signature', function () {
        $trip = ($this->createTrip)();

        $response = getJson(
            route('v1.customers.trip-history.download-receipt', ['trip' => $trip->id])
            . '?expires=99999999999&signature=invalid'
        );

        $response->assertForbidden();
    });

    test('cannot download receipt for non-completed trip even with valid signature', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER]);

        // Manually create a signed URL for the non-completed trip
        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'v1.customers.trip-history.download-receipt',
            now()->addMinutes(10),
            ['trip' => $trip->id]
        );

        $urlParts = parse_url($signedUrl);
        parse_str($urlParts['query'] ?? '', $queryParams);

        $response = getJson(
            route('v1.customers.trip-history.download-receipt', ['trip' => $trip->id])
            . '?' . http_build_query($queryParams)
        );

        $response->assertStatus(406);
    });

    test('receipt link expires after configured time', function () {
        $trip = ($this->createTrip)();
        ($this->createLocation)($trip->id);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.trip-history.receipt-link', ['trip' => $trip->id]));

        $link = $response->json('data.link');
        $urlParts = parse_url($link);
        parse_str($urlParts['query'] ?? '', $queryParams);

        expect($queryParams)->toHaveKey('expires')
            ->and($queryParams['expires'])->toBeGreaterThan(now()->timestamp);
    });
});
