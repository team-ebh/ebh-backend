<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

describe('Upcoming Trips List API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns empty array when customer has no upcoming trips', function () {
        $response = getJson(route('v1.customers.trip-history.upcoming'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    });

    it('returns scheduled draft trips as upcoming trips', function () {
        $order = Order::create([
            Order::COLUMN_CUSTOMER_ID => $this->customer->id,
            Order::COLUMN_TOTAL_PRICE => 10.000,
            Order::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET->value,
            Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED->value,
        ]);

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
            Trip::COLUMN_ORDER_ID => $order->id,
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

        $response = getJson(route('v1.customers.trip-history.upcoming'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.trips')
            ->assertJsonStructure([
                'data' => [
                    'trips' => [
                        '*' => [
                            'id',
                            'locations' => [
                                '*' => ['title', 'sub_title'],
                            ],
                            'schedule_date_time',
                            'type' => ['id', 'label'],
                        ],
                    ],
                    'pagination' => ['has_more_pages', 'next_cursor'],
                ],
            ]);

        // Verify type is present (trip type, not ride type)
        $response->assertJsonPath('data.trips.0.type.id', TripTypeEnum::SCHEDULED->value);
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

        $response = getJson(route('v1.customers.trip-history.upcoming'));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.trips');
    });

    it('orders upcoming trips by scheduled time', function () {
        $order1 = Order::create([
            Order::COLUMN_CUSTOMER_ID => $this->customer->id,
            Order::COLUMN_TOTAL_PRICE => 5.000,
            Order::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET->value,
            Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED->value,
        ]);

        $order2 = Order::create([
            Order::COLUMN_CUSTOMER_ID => $this->customer->id,
            Order::COLUMN_TOTAL_PRICE => 5.000,
            Order::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET->value,
            Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED->value,
        ]);

        $laterTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(5),
            Trip::COLUMN_ORDER_ID => $order1->id,
        ]);

        $soonerTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(1),
            Trip::COLUMN_ORDER_ID => $order2->id,
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

        $response = getJson(route('v1.customers.trip-history.upcoming'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.trips');

        // First trip should be the sooner one
        $response->assertJsonPath('data.trips.0.id', $soonerTrip->id);
        $response->assertJsonPath('data.trips.1.id', $laterTrip->id);
    });

    it('does not include rider information in upcoming trips', function () {
        $rider = Rider::factory()->create();

        $order = Order::create([
            Order::COLUMN_CUSTOMER_ID => $this->customer->id,
            Order::COLUMN_TOTAL_PRICE => 5.000,
            Order::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Order::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET->value,
            Order::COLUMN_STATUS => OrderStatusEnum::COMPLETED->value,
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(2),
            Trip::COLUMN_ORDER_ID => $order->id,
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        $response = getJson(route('v1.customers.trip-history.upcoming'));

        $response->assertStatus(200);
        expect($response->json('data.trips.0'))->not->toHaveKey('rider');
    });
});

describe('Past Trips List API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns empty array when customer has no past trips', function () {
        $response = getJson(route('v1.customers.trip-history.past'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    });

    it('returns completed trips in past trips with status', function () {
        $rider = Rider::factory()->create([
            Rider::COLUMN_FULL_NAME => 'John Doe',
        ]);

        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_make',
            VehicleSetting::COLUMN_NAME => 'Toyota',
            VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_model',
            VehicleSetting::COLUMN_NAME => 'Camry',
            VehicleSetting::COLUMN_NAME_AR => 'كامري',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $rider->id,
            Vehicle::COLUMN_PLATE_NUMBER => 'ABC-123',
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Vehicle::COLUMN_YEAR => 2023,
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
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

        $response = getJson(route('v1.customers.trip-history.past'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.trips')
            ->assertJsonStructure([
                'data' => [
                    'trips' => [
                        '*' => [
                            'id',
                            'rider' => ['id', 'image', 'name', 'rating'],
                            'vehicle' => ['model', 'plate_number'],
                            'locations' => [
                                '*' => ['title', 'sub_title'],
                            ],
                            'status' => ['id', 'label'],
                        ],
                    ],
                    'pagination' => ['has_more_pages', 'next_cursor'],
                ],
            ]);

        // Verify status is present
        $response->assertJsonPath('data.trips.0.status.id', TripStatusEnum::COMPLETED->value);
    });

    it('returns cancelled trips with simple Cancelled label', function () {
        $rider = Rider::factory()->create();

        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_make',
            VehicleSetting::COLUMN_NAME => 'Toyota',
            VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_model',
            VehicleSetting::COLUMN_NAME => 'Camry',
            VehicleSetting::COLUMN_NAME_AR => 'كامري',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $rider->id,
            Vehicle::COLUMN_PLATE_NUMBER => 'ABC-123',
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Vehicle::COLUMN_YEAR => 2023,
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
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

        $response = getJson(route('v1.customers.trip-history.past'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.trips')
            ->assertJsonPath('data.trips.0.status.id', TripStatusEnum::CANCELED_BY_CUSTOMER->value)
            ->assertJsonPath('data.trips.0.status.label', 'Cancelled');
    });

    it('does not return draft or active trips in past trips', function () {
        $rider = Rider::factory()->create();

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
                Trip::COLUMN_RIDER_ID => $rider->id,
                Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
                Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
                Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
                Trip::COLUMN_PASSENGER_COUNT => 1,
                Trip::COLUMN_TOTAL_PRICE => 5.000,
                Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
                Trip::COLUMN_STATUS => $status->value,
            ]);
        }

        $response = getJson(route('v1.customers.trip-history.past'));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.trips');
    });

    it('includes rider information when trip has assigned rider', function () {
        $rider = Rider::factory()->create([
            Rider::COLUMN_FULL_NAME => 'John Doe',
            Rider::COLUMN_PHONE_NUMBER => '+96512345678',
        ]);

        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_make',
            VehicleSetting::COLUMN_NAME => 'Toyota',
            VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_model',
            VehicleSetting::COLUMN_NAME => 'Camry',
            VehicleSetting::COLUMN_NAME_AR => 'كامري',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $rider->id,
            Vehicle::COLUMN_PLATE_NUMBER => 'ABC-123',
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Vehicle::COLUMN_YEAR => 2023,
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
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

        $response = getJson(route('v1.customers.trip-history.past'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'trips' => [
                        '*' => [
                            'rider' => [
                                'id',
                                'image',
                                'name',
                                'rating',
                            ],
                            'vehicle' => [
                                'model',
                                'plate_number',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.trips.0.rider.name', 'John Doe')
            ->assertJsonPath('data.trips.0.vehicle.plate_number', 'ABC-123');
    });

    it('orders past trips by id descending (most recent first)', function () {
        $rider = Rider::factory()->create();

        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_make',
            VehicleSetting::COLUMN_NAME => 'Toyota',
            VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_model',
            VehicleSetting::COLUMN_NAME => 'Camry',
            VehicleSetting::COLUMN_NAME_AR => 'كامري',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $rider->id,
            Vehicle::COLUMN_PLATE_NUMBER => 'ABC-123',
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Vehicle::COLUMN_YEAR => 2023,
        ]);

        $olderTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        $newerTrip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
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
        }

        $response = getJson(route('v1.customers.trip-history.past'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.trips');

        // Newer trip should be first
        $response->assertJsonPath('data.trips.0.id', $newerTrip->id);
        $response->assertJsonPath('data.trips.1.id', $olderTrip->id);
    });
});

describe('Upcoming Trip Details API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns detailed upcoming trip information', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 2,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 7.500,
            Trip::COLUMN_ACCESSIBILITY_PRICE => 2.500,
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

        $response = getJson(route('v1.customers.trip-history.upcoming.details', $trip));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'schedule_date_time',
                    'locations' => [
                        '*' => ['title', 'sub_title'],
                    ],
                    'price_breakdown',
                    'type' => ['id', 'label'],
                    'passenger_count',
                ],
            ]);

        // Verify no rider info in upcoming details
        expect($response->json('data'))->not->toHaveKey('rider');
        expect($response->json('data'))->not->toHaveKey('vehicle_plate_number');
    });

    it('returns accessibility requirements for upcoming trip', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(2),
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        TripAccessibility::create([
            TripAccessibility::COLUMN_TRIP_ID => $trip->id,
            TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE,
        ]);

        $response = getJson(route('v1.customers.trip-history.upcoming.details', $trip));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'accessibility' => [
                        '*' => ['id', 'label', 'description', 'icon'],
                    ],
                ],
            ]);
    });

    it('returns ride type for round trip with wait', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ROUND_TRIP_WAIT->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 10.000,
            Trip::COLUMN_WAITING_TIME => 45,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(2),
        ]);

        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Origin',
            TripLocation::COLUMN_LATITUDE => 29.3759,
            TripLocation::COLUMN_LONGITUDE => 47.9774,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        $response = getJson(route('v1.customers.trip-history.upcoming.details', $trip));

        $response->assertStatus(200)
            ->assertJsonPath('data.type.id', RideTypeEnum::ROUND_TRIP_WAIT->value);
    });

    it('cannot get upcoming details of trip belonging to another customer', function () {
        $otherCustomer = Customer::factory()->create();

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $otherCustomer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(2),
        ]);

        $response = getJson(route('v1.customers.trip-history.upcoming.details', $trip));

        $response->assertStatus(403);
    });

    it('cannot get upcoming details of a past trip', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        $response = getJson(route('v1.customers.trip-history.upcoming.details', $trip));

        $response->assertStatus(403);
    });
});

describe('Past Trip Details API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('returns detailed past trip information with rider and vehicle plate', function () {
        $rider = Rider::factory()->create([
            Rider::COLUMN_FULL_NAME => 'Test Rider',
            Rider::COLUMN_PHONE_NUMBER => '+96587654321',
        ]);

        // Create required vehicle settings
        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_make',
            VehicleSetting::COLUMN_NAME => 'Toyota',
            VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_model',
            VehicleSetting::COLUMN_NAME => 'Camry',
            VehicleSetting::COLUMN_NAME_AR => 'كامري',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $vehicle = Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $rider->id,
            Vehicle::COLUMN_PLATE_NUMBER => 'ABC-123',
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Vehicle::COLUMN_YEAR => 2023,
        ]);

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_RIDER_ID => $rider->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 2,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
            Trip::COLUMN_VEHICLE_SNAPSHOT => [
                'car_make' => 'Toyota',
                'car_model' => 'Camry',
                'model' => 'Toyota Camry',
                'plate_number' => 'ABC-123',
                'year' => 2023,
            ],
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

        $response = getJson(route('v1.customers.trip-history.past.details', $trip));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'rider' => ['id', 'image', 'name', 'rating'],
                    'vehicle' => ['model', 'plate_number'],
                    'status' => ['id', 'label'],
                    'locations' => [
                        '*' => ['title', 'sub_title'],
                    ],
                    'price_breakdown',
                    'ride_type' => ['id', 'label'],
                    'passenger_count',
                    'date_time',
                ],
            ])
            ->assertJsonPath('data.rider.name', 'Test Rider')
            ->assertJsonPath('data.vehicle.plate_number', 'ABC-123')
            ->assertJsonPath('data.vehicle.model', 'Toyota Camry');

        // Verify no schedule_date_time in past details
        expect($response->json('data'))->not->toHaveKey('schedule_date_time');
    });

    it('returns accessibility requirements for past trip', function () {
        $rider = Rider::factory()->create();

        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_make',
            VehicleSetting::COLUMN_NAME => 'Honda',
            VehicleSetting::COLUMN_NAME_AR => 'هوندا',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => 'car_model',
            VehicleSetting::COLUMN_NAME => 'Accord',
            VehicleSetting::COLUMN_NAME_AR => 'أكورد',
            VehicleSetting::COLUMN_ORDER => 1,
        ]);

        Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $rider->id,
            Vehicle::COLUMN_PLATE_NUMBER => 'XYZ-789',
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Vehicle::COLUMN_YEAR => 2023,
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

        TripAccessibility::create([
            TripAccessibility::COLUMN_TRIP_ID => $trip->id,
            TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE,
        ]);

        TripAccessibility::create([
            TripAccessibility::COLUMN_TRIP_ID => $trip->id,
            TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => AccessibilityRequirementsEnum::OXYGEN_SUPPORT,
        ]);

        $response = getJson(route('v1.customers.trip-history.past.details', $trip));

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

    it('cannot get past details of trip belonging to another customer', function () {
        $otherCustomer = Customer::factory()->create();

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $otherCustomer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
        ]);

        $response = getJson(route('v1.customers.trip-history.past.details', $trip));

        $response->assertStatus(403);
    });

    it('cannot get past details of an upcoming trip', function () {
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHours(2),
        ]);

        $response = getJson(route('v1.customers.trip-history.past.details', $trip));

        $response->assertStatus(403);
    });
});
