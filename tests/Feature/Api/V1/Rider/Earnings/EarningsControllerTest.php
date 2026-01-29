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
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create();
    $this->customer = Customer::factory()->create();

    $this->createCompletedTrip = function (array $overrides = []) {
        return Trip::create(array_merge([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_COMMISSION_RATE => 10.00,
            Trip::COLUMN_COMMISSION_AMOUNT => 1.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Trip::COLUMN_DURATION_MINUTES => 30,
            Trip::COLUMN_COMPLETED_AT => now(),
        ], $overrides));
    };

    $this->createLocation = function (int $tripId, array $overrides = []) {
        return TripLocation::create(array_merge([
            TripLocation::COLUMN_TRIP_ID => $tripId,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::COMPLETED,
            TripLocation::COLUMN_LOCATION_TITLE => 'Kuwait Hospital',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_SEQUENCE => 1,
        ], $overrides));
    };
});

describe('Get Earnings Filters API', function () {
    test('rider can get earnings filters', function () {
        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.filters'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'filters' => [
                        '*' => ['id', 'label', 'is_default'],
                    ],
                ],
            ]);

        expect($response->json('data.filters'))->toHaveCount(3)
            ->and($response->json('data.filters.0.id'))->toBe('today')
            ->and($response->json('data.filters.0.is_default'))->toBeTrue()
            ->and($response->json('data.filters.1.id'))->toBe('this_week')
            ->and($response->json('data.filters.1.is_default'))->toBeFalse()
            ->and($response->json('data.filters.2.id'))->toBe('past_trips')
            ->and($response->json('data.filters.2.is_default'))->toBeFalse();
    });

    test('unauthenticated rider cannot get earnings filters', function () {
        $response = getJson(route('v1.riders.earnings.filters'));

        $response->assertUnauthorized();
    });
});

describe('Get Earnings Report API', function () {
    test('rider can get earnings report with today filter', function () {
        // Create trips completed today
        $trip1 = ($this->createCompletedTrip)([
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_COMMISSION_AMOUNT => 1.000,
            Trip::COLUMN_COMPLETED_AT => now(),
        ]);
        ($this->createLocation)($trip1->id);

        $trip2 = ($this->createCompletedTrip)([
            Trip::COLUMN_TOTAL_PRICE => 15.000,
            Trip::COLUMN_COMMISSION_AMOUNT => 1.500,
            Trip::COLUMN_COMPLETED_AT => now(),
        ]);
        ($this->createLocation)($trip2->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.report', ['filter' => 'today']));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_earnings',
                    'currency',
                    'change_percentage',
                    'change_direction',
                    'comparison_text',
                    'total_rides',
                    'total_minutes',
                    'average_per_ride',
                    'is_empty',
                ],
            ]);

        // Total earnings = (10 - 1) + (15 - 1.5) = 9 + 13.5 = 22.5
        expect($response->json('data.total_earnings'))->toBe(22.5)
            ->and($response->json('data.currency'))->toBe('KWD')
            ->and($response->json('data.total_rides'))->toBe(2)
            ->and($response->json('data.is_empty'))->toBeFalse();
    });

    test('rider can get earnings report with this_week filter', function () {
        // Create trips completed this week
        $trip = ($this->createCompletedTrip)([
            Trip::COLUMN_TOTAL_PRICE => 20.000,
            Trip::COLUMN_COMMISSION_AMOUNT => 2.000,
            Trip::COLUMN_COMPLETED_AT => Carbon::now()->startOfWeek()->addDays(2),
        ]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.report', ['filter' => 'this_week']));

        $response->assertOk();
        expect($response->json('data.total_earnings'))->toEqual(18.0)
            ->and($response->json('data.total_rides'))->toEqual(1);
    });

    test('rider gets empty report when no trips exist', function () {
        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.report', ['filter' => 'today']));

        $response->assertOk();
        expect($response->json('data.total_earnings'))->toEqual(0)
            ->and($response->json('data.total_rides'))->toEqual(0)
            ->and($response->json('data.is_empty'))->toBeTrue()
            ->and($response->json('data.average_per_ride'))->toEqual(0);
    });

    test('rider sees change percentage compared to previous period', function () {
        // Create trip completed yesterday
        $yesterdayTrip = ($this->createCompletedTrip)([
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_COMMISSION_AMOUNT => 1.000,
            Trip::COLUMN_COMPLETED_AT => Carbon::yesterday(),
        ]);
        ($this->createLocation)($yesterdayTrip->id);

        // Create trip completed today (double the earnings)
        $todayTrip = ($this->createCompletedTrip)([
            Trip::COLUMN_TOTAL_PRICE => 20.000,
            Trip::COLUMN_COMMISSION_AMOUNT => 2.000,
            Trip::COLUMN_COMPLETED_AT => now(),
        ]);
        ($this->createLocation)($todayTrip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.report', ['filter' => 'today']));

        $response->assertOk();
        // Today: 18, Yesterday: 9, Change: +100%
        expect($response->json('data.change_percentage'))->toBe(100)
            ->and($response->json('data.change_direction'))->toBe('up');
    });

    test('report uses today filter by default', function () {
        $trip = ($this->createCompletedTrip)([
            Trip::COLUMN_COMPLETED_AT => now(),
        ]);
        ($this->createLocation)($trip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.report'));

        $response->assertOk();
        expect($response->json('data.total_rides'))->toBe(1);
    });

    test('report validation fails for invalid filter', function () {
        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.report', ['filter' => 'invalid']));

        $response->assertStatus(422);
    });

    test('unauthenticated rider cannot get earnings report', function () {
        $response = getJson(route('v1.riders.earnings.report'));

        $response->assertUnauthorized();
    });
});

describe('Get Latest Trips API', function () {
    test('rider can get latest trips for earnings', function () {
        $trip = ($this->createCompletedTrip)([
            Trip::COLUMN_COMPLETED_AT => now(),
        ]);
        ($this->createLocation)($trip->id);
        ($this->createLocation)($trip->id, [
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_LOCATION_TITLE => 'Al-Rahab',
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.latest-trips'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'trips' => [
                        '*' => [
                            'id',
                            'locations' => [
                                '*' => ['title', 'sub_title'],
                            ],
                            'date_time',
                            'price',
                            'currency',
                        ],
                    ],
                    'pagination' => ['next_cursor', 'has_more_pages'],
                ],
            ]);

        expect($response->json('data.trips'))->toHaveCount(1)
            ->and($response->json('data.trips.0.locations'))->toHaveCount(2)
            ->and($response->json('data.trips.0.locations.0.title'))->toBe('Kuwait Hospital')
            ->and($response->json('data.trips.0.locations.1.title'))->toBe('Al-Rahab')
            ->and($response->json('data.trips.0.price'))->toEqual(10.0)
            ->and($response->json('data.trips.0.currency'))->toBe('KWD');
    });

    test('rider only sees completed trips in latest trips', function () {
        // Create completed trip
        $completedTrip = ($this->createCompletedTrip)([
            Trip::COLUMN_COMPLETED_AT => now(),
        ]);
        ($this->createLocation)($completedTrip->id);

        // Create cancelled trip (should not appear)
        $cancelledTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $this->rider->id,
            Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        ]);
        ($this->createLocation)($cancelledTrip->id);

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.latest-trips'));

        $response->assertOk();
        expect($response->json('data.trips'))->toHaveCount(1);
    });

    test('latest trips supports pagination', function () {
        // Create 7 trips to test pagination (5 per page)
        for ($i = 0; $i < 7; $i++) {
            $trip = ($this->createCompletedTrip)([
                Trip::COLUMN_COMPLETED_AT => now()->subMinutes($i),
            ]);
            ($this->createLocation)($trip->id);
        }

        $response = actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.earnings.latest-trips'));

        $response->assertOk();
        expect($response->json('data.trips'))->toHaveCount(5)
            ->and($response->json('data.pagination.has_more_pages'))->toBeTrue()
            ->and($response->json('data.pagination.next_cursor'))->not->toBeNull();
    });

    test('unauthenticated rider cannot get latest trips', function () {
        $response = getJson(route('v1.riders.earnings.latest-trips'));

        $response->assertUnauthorized();
    });
});
