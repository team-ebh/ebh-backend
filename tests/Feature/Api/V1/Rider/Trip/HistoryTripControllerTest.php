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
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create();
    $this->otherRider = Rider::factory()->create();
    $this->customer = Customer::factory()->create();

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

describe('Get Filters API', function () {
    test('rider can get trip history filters with statistics', function () {
        // Create completed trip
        $trip1 = ($this->createTrip)();
        ($this->createLocation)($trip1->id);

        // Create cancelled trip
        $trip2 = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
        ($this->createLocation)($trip2->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.filters'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'filters' => [
                        '*' => ['id', 'label', 'is_default'],
                    ],
                    'total_rides_count',
                    'canceled_count',
                ],
            ]);

        expect($response->json('data.filters'))->toHaveCount(3)
            ->and($response->json('data.filters.0.id'))->toBe('all')
            ->and($response->json('data.filters.0.is_default'))->toBeTrue()
            ->and($response->json('data.filters.1.id'))->toBe('completed')
            ->and($response->json('data.filters.1.is_default'))->toBeFalse()
            ->and($response->json('data.filters.2.id'))->toBe('canceled')
            ->and($response->json('data.filters.2.is_default'))->toBeFalse()
            ->and($response->json('data.total_rides_count'))->toBe(1)
            ->and($response->json('data.canceled_count'))->toBe(1);
    });

    test('rider sees correct counts with multiple trips', function () {
        // Create 3 completed trips
        for ($i = 0; $i < 3; $i++) {
            $trip = ($this->createTrip)();
            ($this->createLocation)($trip->id);
        }

        // Create 2 cancelled trips
        for ($i = 0; $i < 2; $i++) {
            $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
            ($this->createLocation)($trip->id);
        }

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.filters'));

        $response->assertOk();
        expect($response->json('data.total_rides_count'))->toBe(3)
            ->and($response->json('data.canceled_count'))->toBe(2);
    });

    test('unauthenticated rider cannot get filters', function () {
        $response = getJson(route('v1.riders.trip-history.filters'));

        $response->assertUnauthorized();
    });
});

describe('Get Past Trips List API', function () {
    test('rider can get their past trips list', function () {
        $trip1 = ($this->createTrip)();
        ($this->createLocation)($trip1->id);

        $trip2 = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
        ($this->createLocation)($trip2->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'trips' => [
                        '*' => [
                            'id',
                            'locations',
                            'status' => ['id', 'label'],
                            'date_time',
                        ],
                    ],
                    'pagination' => ['next_cursor', 'has_more_pages'],
                ],
            ]);

        expect($response->json('data.trips'))->toHaveCount(2);
    });

    test('rider can filter trips by completed status', function () {
        // Create completed trip
        $completedTrip = ($this->createTrip)();
        ($this->createLocation)($completedTrip->id);

        // Create cancelled trip
        $cancelledTrip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
        ($this->createLocation)($cancelledTrip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.index', ['filter' => 'completed']));

        $response->assertOk();
        expect($response->json('data.trips'))->toHaveCount(1)
            ->and($response->json('data.trips.0.id'))->toBe($completedTrip->id);
    });

    test('rider can filter trips by canceled status', function () {
        // Create completed trip
        $completedTrip = ($this->createTrip)();
        ($this->createLocation)($completedTrip->id);

        // Create cancelled trips
        $cancelledByRider = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
        ($this->createLocation)($cancelledByRider->id);

        $cancelledByCustomer = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER]);
        ($this->createLocation)($cancelledByCustomer->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.index', ['filter' => 'canceled']));

        $response->assertOk();
        expect($response->json('data.trips'))->toHaveCount(2);
    });

    test('invalid filter returns validation error', function () {
        $trip = ($this->createTrip)();
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.index', ['filter' => 'invalid']));

        $response->assertUnprocessable();
    });

    test('rider only sees their own past trips', function () {
        // Create trip for this rider
        $trip = ($this->createTrip)();
        ($this->createLocation)($trip->id);

        // Create trip for another rider
        $otherTrip = ($this->createTrip)([Trip::COLUMN_RIDER_ID => $this->otherRider->id]);
        ($this->createLocation)($otherTrip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.index'));

        $response->assertOk();
        expect($response->json('data.trips'))->toHaveCount(1);
        expect($response->json('data.trips.0.id'))->toBe($trip->id);
    });

    test('rider does not see active trips in past trips list', function () {
        // Create completed trip
        $completedTrip = ($this->createTrip)();
        ($this->createLocation)($completedTrip->id);

        // Create active trip
        $activeTrip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER]);
        ($this->createLocation)($activeTrip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.index'));

        $response->assertOk();
        expect($response->json('data.trips'))->toHaveCount(1);
        expect($response->json('data.trips.0.id'))->toBe($completedTrip->id);
    });

    test('unauthenticated rider cannot get past trips', function () {
        $response = getJson(route('v1.riders.trip-history.index'));

        $response->assertUnauthorized();
    });
});

describe('Get Past Trip Details API', function () {
    test('rider can get past trip details', function () {
        $trip = ($this->createTrip)();
        ($this->createLocation)($trip->id);
        ($this->createLocation)($trip->id, [
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.show', ['trip' => $trip->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status' => ['id', 'label'],
                    'locations',
                    'price_breakdown',
                    'ride_type' => ['id', 'label'],
                    'date_time',
                ],
            ]);
    });

    test('rider cannot get another rider past trip details', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_RIDER_ID => $this->otherRider->id]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.show', ['trip' => $trip->id]));

        $response->assertForbidden();
    });

    test('rider cannot get active trip details from past trips endpoint', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.show', ['trip' => $trip->id]));

        $response->assertForbidden();
    });

    test('unauthenticated rider cannot get past trip details', function () {
        $trip = ($this->createTrip)();

        $response = getJson(route('v1.riders.trip-history.show', ['trip' => $trip->id]));

        $response->assertUnauthorized();
    });
});

describe('Receipt Link API', function () {
    test('rider can get receipt link for completed trip', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['link'],
            ]);

        expect($response->json('data.link'))->toContain('download-receipt');
    });

    test('rider cannot get receipt link for another rider trip', function () {
        $trip = ($this->createTrip)([
            Trip::COLUMN_RIDER_ID => $this->otherRider->id,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        ]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertForbidden();
    });

    test('rider cannot get receipt link for non-completed trip', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertStatus(406); // TripNotCompletedException
    });

    test('unauthenticated rider cannot get receipt link', function () {
        $trip = ($this->createTrip)();

        $response = getJson(route('v1.riders.trip-history.receipt-link', ['trip' => $trip->id]));

        $response->assertUnauthorized();
    });
});

describe('Download Receipt API', function () {
    test('rider can download receipt with valid signed URL', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED]);
        ($this->createLocation)($trip->id);

        $signedUrl = URL::temporarySignedRoute(
            'v1.riders.trip-history.download-receipt',
            now()->addMinutes(10),
            ['trip' => $trip->id]
        );

        $response = $this->get($signedUrl);

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('pdf');
    });

    test('rider cannot download receipt without valid signature', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED]);
        ($this->createLocation)($trip->id);

        $response = getJson(route('v1.riders.trip-history.download-receipt', ['trip' => $trip->id]));

        $response->assertForbidden(); // InvalidRequestException
    });

    test('rider cannot download receipt for non-completed trip', function () {
        $trip = ($this->createTrip)([Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER]);
        ($this->createLocation)($trip->id);

        $signedUrl = URL::temporarySignedRoute(
            'v1.riders.trip-history.download-receipt',
            now()->addMinutes(10),
            ['trip' => $trip->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(406); // TripNotCompletedException
    });
});
