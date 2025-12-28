<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
});

test('customer can get active trip when one exists', function () {
    // Create an active trip for the customer (CONFIRMED status)
    $rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::BUSY,
    ]);

    // Create vehicle settings for car make and model
    $carMake = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MAKES,
        VehicleSetting::COLUMN_NAME => 'Toyota',
        VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    $carModel = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MODELS,
        VehicleSetting::COLUMN_NAME => 'Camry',
        VehicleSetting::COLUMN_NAME_AR => 'كامري',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    $vehicleType = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_VEHICLE_TYPES,
        VehicleSetting::COLUMN_NAME => 'Sedan',
        VehicleSetting::COLUMN_NAME_AR => 'سيدان',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    // Create vehicle for rider
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
        Vehicle::COLUMN_CAR_MAKE_ID => $carMake->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_CAR_MODEL_ID => $carModel->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_VEHICLE_TYPE_ID => $vehicleType->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
        Vehicle::COLUMN_YEAR => 2023,
    ]);

    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'found',
                'id',
                'status',
                'arrived_time',
                'rider',
                'vehicle',
                'map_locations',
                'formatted_locations',
            ],
        ])
        ->assertJson([
            'data' => [
                'found' => true,
                'id' => $trip->{Trip::COLUMN_ID},
            ],
        ]);
});

test('customer gets null when no active trip exists', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJson([
            'data' => null,
        ]);
});

test('customer gets null when all trips are completed', function () {
    // Create a completed trip
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJson([
            'data' => null,
        ]);
});

test('customer gets null when all trips are cancelled', function () {
    // Create a cancelled trip
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
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJson([
            'data' => null,
        ]);
});

test('customer can get active trip with PENDING_RIDER status', function () {
    // Create a trip with PENDING_RIDER status (still active)
    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'found',
                'id',
                'status',
                'arrived_time',
                'map_locations',
                'formatted_locations',
            ],
        ])
        ->assertJson([
            'data' => [
                'found' => false,
                'id' => $trip->{Trip::COLUMN_ID},
            ],
        ]);
});

test('customer can get active trip with IN_PROGRESS status', function () {
    $rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::BUSY,
    ]);

    // Create vehicle settings for car make and model
    $carMake = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MAKES,
        VehicleSetting::COLUMN_NAME => 'Honda',
        VehicleSetting::COLUMN_NAME_AR => 'هوندا',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    $carModel = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MODELS,
        VehicleSetting::COLUMN_NAME => 'Accord',
        VehicleSetting::COLUMN_NAME_AR => 'أكورد',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    $vehicleType = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_VEHICLE_TYPES,
        VehicleSetting::COLUMN_NAME => 'Sedan',
        VehicleSetting::COLUMN_NAME_AR => 'سيدان',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    // Create vehicle for rider
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
        Vehicle::COLUMN_CAR_MAKE_ID => $carMake->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_CAR_MODEL_ID => $carModel->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_VEHICLE_TYPE_ID => $vehicleType->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_PLATE_NUMBER => 'XYZ789',
        Vehicle::COLUMN_YEAR => 2022,
    ]);

    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'found',
                'id',
                'status',
                'arrived_time',
                'rider',
                'vehicle',
                'map_locations',
                'formatted_locations',
            ],
        ])
        ->assertJson([
            'data' => [
                'found' => true,
                'id' => $trip->{Trip::COLUMN_ID},
            ],
        ]);
});

test('unauthenticated customer cannot get active trip', function () {
    $response = getJson(route('v1.customers.trips.active'));

    $response->assertUnauthorized();
});

test('customer only sees their own active trip', function () {
    $otherCustomer = Customer::factory()->create();

    // Create active trip for other customer
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $otherCustomer->{Customer::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $response->assertOk()
        ->assertJson([
            'data' => null,
        ]);
});

test('active trip API does not have N+1 query problem', function () {
    $rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::BUSY,
    ]);

    // Create vehicle settings for car make and model
    $carMake = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MAKES,
        VehicleSetting::COLUMN_NAME => 'Nissan',
        VehicleSetting::COLUMN_NAME_AR => 'نيسان',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    $carModel = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MODELS,
        VehicleSetting::COLUMN_NAME => 'Altima',
        VehicleSetting::COLUMN_NAME_AR => 'ألتيما',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    $vehicleType = VehicleSetting::create([
        VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_VEHICLE_TYPES,
        VehicleSetting::COLUMN_NAME => 'Sedan',
        VehicleSetting::COLUMN_NAME_AR => 'سيدان',
        VehicleSetting::COLUMN_ORDER => 1,
    ]);

    // Create vehicle for rider
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
        Vehicle::COLUMN_CAR_MAKE_ID => $carMake->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_CAR_MODEL_ID => $carModel->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_VEHICLE_TYPE_ID => $vehicleType->{VehicleSetting::COLUMN_ID},
        Vehicle::COLUMN_PLATE_NUMBER => 'DEF456',
        Vehicle::COLUMN_YEAR => 2021,
    ]);

    // Create trip with rider and vehicle
    Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
        Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
    ]);

    // Enable query logging
    DB::enableQueryLog();

    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.trips.active'));

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $response->assertOk();

    // Expected queries (all properly eager loaded - no N+1 problem):
    // 1. Get active trip (with conditions)
    // 2. Get rider (eager loaded)
    // 3. Get rider accessibility certifications (eager loaded)
    // 4. Get vehicle (eager loaded)
    // 5. Get carMake (eager loaded from vehicle_settings)
    // 6. Get carModel (eager loaded from vehicle_settings)
    // 7. Get media for rider profile photo
    // 8. Get trip locations (lazy loaded in BuildLocationHistoryPipe)
    // Total: 8 queries, all necessary and optimized
    expect(count($queries))->toBeLessThanOrEqual(9);
});
