<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

describe('Trip History API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('validates type parameter is required', function () {
        $response = getJson(route('v1.customers.trip-history.trips'));

        $response->assertStatus(422)
            ->assertJsonPath('meta.errors.0.field', 'type');
    });

    it('validates type parameter must be valid enum value', function () {
        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'invalid']));

        $response->assertStatus(422)
            ->assertJsonPath('meta.errors.0.field', 'type');
    });
});

describe('Upcoming Trips API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns empty array when customer has no upcoming trips', function () {
        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'upcoming']));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    });

    it('returns scheduled draft trips as upcoming trips with label showing trip type', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 2,
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_BASE_FARE => 8.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(2),
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin Location',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Sub origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination Location',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Sub destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'upcoming']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'label' => ['id', 'label'],
                        'locations' => [
                            '*' => ['title', 'sub_title', 'type' => ['id', 'label'], 'sequence'],
                        ],
                        'scheduled_time',
                        'created_at',
                    ],
                ],
            ]);

        // Verify label is trip type (Scheduled) for upcoming
        $response->assertJsonPath('data.0.label.id', TripTypeEnum::SCHEDULED->value);

        // Verify locations are sorted by sequence
        $response->assertJsonPath('data.0.locations.0.sequence', 1);
        $response->assertJsonPath('data.0.locations.1.sequence', 2);
    });

    it('does not return ride now draft trips as upcoming', function () {
        Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
        ]);

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'upcoming']));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('orders upcoming trips by scheduled time', function () {
        $laterTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(5),
        ]);

        $soonerTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(1),
        ]);

        foreach ([$laterTrip, $soonerTrip] as $trip) {
            TripLocation::create([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_SEQUENCE => 1,
            ]);
            TripLocation::create([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
                TripLocation::COLUMN_LATITUDE => 29.3117,
                TripLocation::COLUMN_LONGITUDE => 47.4818,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_SEQUENCE => 2,
            ]);
        }

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'upcoming']));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // First trip should be the sooner one
        $response->assertJsonPath('data.0.id', $soonerTrip->id);
        $response->assertJsonPath('data.1.id', $laterTrip->id);
    });
});

describe('Past Trips API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns empty array when customer has no past trips', function () {
        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    });

    it('returns completed trips in past trips with label showing simple status', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'label' => ['id', 'label'],
                        'locations' => [
                            '*' => ['title', 'sub_title', 'type' => ['id', 'label'], 'sequence'],
                        ],
                        'scheduled_time',
                        'created_at',
                    ],
                ],
            ]);

        // Verify label is simple status (Completed) for past
        $response->assertJsonPath('data.0.label.id', TripStatusEnum::COMPLETED->value);
    });

    it('returns cancelled trips with simple Cancelled label', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label.id', TripStatusEnum::CANCELED_BY_CUSTOMER->value)
            ->assertJsonPath('data.0.label.label', 'Cancelled'); // Simple label
    });

    it('returns cancelled by rider trips in past trips', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label.id', TripStatusEnum::CANCELLED_BY_RIDER->value)
            ->assertJsonPath('data.0.label.label', 'Cancelled'); // Simple label - same as cancelled by customer
    });

    it('does not return draft or active trips in past trips', function () {
        $statuses = [
            TripStatusEnum::DRAFT,
            TripStatusEnum::PENDING_RIDER,
            TripStatusEnum::ACCEPTED_RIDER,
            TripStatusEnum::ARRIVED,
            TripStatusEnum::IN_PROGRESS,
        ];

        foreach ($statuses as $status) {
            Trip::create([
                Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
                Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
                Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
                Trip::COLUMN_PASSENGER_COUNT => 1,
                Trip::COLUMN_TOTAL_PRICE => 5.000,
                Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
                Trip::COLUMN_STATUS => $status->value,
            ]);
        }

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('includes rider information when trip has assigned rider', function () {
        $rider = Rider::factory()->create([
            Rider::COLUMN_FULL_NAME => 'John Doe',
            Rider::COLUMN_PHONE_NUMBER => '+96512345678',
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'rider' => [
                            'id',
                            'name',
                            'phone',
                            'rating',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.0.rider.name', 'John Doe');
    });

    it('orders past trips by id descending (most recent first)', function () {
        $olderTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        $newerTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        foreach ([$olderTrip, $newerTrip] as $trip) {
            TripLocation::create([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
                TripLocation::COLUMN_LATITUDE => 29.3759,
                TripLocation::COLUMN_LONGITUDE => 47.9774,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_SEQUENCE => 1,
            ]);
            TripLocation::create([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
                TripLocation::COLUMN_LATITUDE => 29.3117,
                TripLocation::COLUMN_LONGITUDE => 47.4818,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_SEQUENCE => 2,
            ]);
        }

        $response = getJson(route('v1.customers.trip-history.trips', ['type' => 'past']));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Newer trip should be first
        $response->assertJsonPath('data.0.id', $newerTrip->id);
        $response->assertJsonPath('data.1.id', $olderTrip->id);
    });
});

describe('Trip Details API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns detailed trip information', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 2,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 7.500,
            Trip::COLUMN_ACCESSIBILITY_PRICE => 2.500,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin Location',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Sub origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination Location',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Sub destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status' => ['id', 'label'],
                    'locations' => [
                        'from' => ['location', 'sub_location', 'lat', 'lng'],
                        'to' => ['location', 'sub_location', 'lat', 'lng'],
                    ],
                    'price_breakdown',
                    'ride_details' => [
                        'ride_type' => ['id', 'label', 'description', 'icon'],
                        'passenger_count',
                        'waiting_time',
                    ],
                    'scheduled_time',
                    'created_at',
                ],
            ]);
    });

    it('returns price breakdown with all components', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 7.500,
            Trip::COLUMN_ACCESSIBILITY_PRICE => 2.500,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        TripAccessibility::create([
            TripAccessibility::COLUMN_TRIP_ID => $trip->id,
            TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(200);

        $priceBreakdown = $response->json('data.price_breakdown');
        expect($priceBreakdown)->toBeArray()
            ->and(count($priceBreakdown))->toBeGreaterThanOrEqual(2);
    });

    it('returns ride details with ride type enum', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ROUND_TRIP_WAIT->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 3,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_ROUND_TRIP_PRICE => 5.000,
            Trip::COLUMN_WAITING_TIME => 45,
            Trip::COLUMN_WAITING_PRICE => 2.500,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(200)
            ->assertJsonPath('data.ride_details.ride_type.id', RideTypeEnum::ROUND_TRIP_WAIT->value)
            ->assertJsonPath('data.ride_details.passenger_count', 3)
            ->assertJsonPath('data.ride_details.waiting_time', 45);
    });

    it('returns accessibility requirements selected for the trip', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        TripAccessibility::create([
            TripAccessibility::COLUMN_TRIP_ID => $trip->id,
            TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE,
        ]);

        TripAccessibility::create([
            TripAccessibility::COLUMN_TRIP_ID => $trip->id,
            TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::OXYGEN_SUPPORT,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'accessibility' => [
                        '*' => ['id', 'label', 'description', 'icon'],
                    ],
                ],
            ]);

        expect(count($response->json('data.accessibility')))->toBe(2);
    });

    it('does not include accessibility if trip has none', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(200);
        expect($response->json('data.accessibility'))->toBeNull();
    });

    it('cannot get details of trip belonging to another customer', function () {
        $otherCustomer = Customer::factory()->create();

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $otherCustomer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(403);
    });

    it('includes rider information when trip has assigned rider', function () {
        $rider = Rider::factory()->create([
            Rider::COLUMN_FULL_NAME => 'Test Rider',
            Rider::COLUMN_PHONE_NUMBER => '+96587654321',
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Destination',
            TripLocation::COLUMN_LATITUDE => 29.3117,
            TripLocation::COLUMN_LONGITUDE => 47.4818,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        $response = getJson(route('v1.customers.trip-history.details', $trip));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'rider' => [
                        'id',
                        'name',
                        'phone',
                        'rating',
                    ],
                ],
            ])
            ->assertJsonPath('data.rider.name', 'Test Rider');
    });
});
