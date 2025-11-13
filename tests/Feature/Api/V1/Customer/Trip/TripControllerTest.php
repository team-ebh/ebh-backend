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
use App\Models\Trip;
use App\Models\TripLocation;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\{get, postJson, withHeaders, getJson};

describe('V1 Customer Trip API', function () {
    it('can get trip form data', function () {
        $response = withHeaders(['Host' => 'api.localhost'])
            ->get(route('v1.customers.trips.form-data'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'trip_types' => [
                        '*' => [
                            'id',
                            'label',
                        ],
                    ],
                    'vehicle_types' => [
                        '*' => [
                            'id',
                            'label',
                            'description',
                        ],
                    ],
                    'accessibility_requirements' => [
                        '*' => [
                            'id',
                            'label',
                            'description',
                        ],
                    ],
                    'maximum_passengers',
                ],
            ]);

        // Verify trip types
        $tripTypes = $response->json('data.trip_types');
        expect($tripTypes)->toHaveCount(2);

        $tripTypeIds = collect($tripTypes)->pluck('id')->toArray();
        expect($tripTypeIds)->toContain(TripTypeEnum::RIDE_NOW->value);
        expect($tripTypeIds)->toContain(TripTypeEnum::SCHEDULED->value);

        // Verify vehicle types
        $vehicleTypes = $response->json('data.vehicle_types');
        expect($vehicleTypes)->toHaveCount(2);

        $vehicleTypeIds = collect($vehicleTypes)->pluck('id')->toArray();
        expect($vehicleTypeIds)->toContain(TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value)
            ->and($vehicleTypeIds)->toContain(TripVehicleTypeEnum::BED_TRANSPORT->value);

        // Verify accessibility requirements
        $accessibilityRequirements = $response->json('data.accessibility_requirements');
        expect($accessibilityRequirements)->toHaveCount(3);

        $accessibilityIds = collect($accessibilityRequirements)->pluck('id')->toArray();
        expect($accessibilityIds)->toContain(AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->value)
            ->and($accessibilityIds)->toContain(AccessibilityRequirementsEnum::OXYGEN_SUPPORT->value)
            ->and($accessibilityIds)->toContain(AccessibilityRequirementsEnum::PORTABLE_RAMP->value)
            ->and($response->json('data.maximum_passengers'))->toBe(6);

        // Verify maximum passengers
    });

    it('returns correct labels for trip types', function () {
        $response = get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        $tripTypes = $response->json('data.trip_types');

        $rideNowType = collect($tripTypes)->firstWhere('id', TripTypeEnum::RIDE_NOW->value);
        expect($rideNowType['label'])->toBe('Ride Now');

        $scheduledType = collect($tripTypes)->firstWhere('id', TripTypeEnum::SCHEDULED->value);
        expect($scheduledType['label'])->toBe('Scheduled');
    });

    it('returns correct labels and descriptions for vehicle types', function () {
        $response = get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        $vehicleTypes = $response->json('data.vehicle_types');

        $wheelchairType = collect($vehicleTypes)->firstWhere('id', TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value);
        expect($wheelchairType['label'])->toBe('Wheelchair Accessible')
            ->and($wheelchairType['description'])->toBe('Standard Wheelchair transport');

        $bedTransportType = collect($vehicleTypes)->firstWhere('id', TripVehicleTypeEnum::BED_TRANSPORT->value);
        expect($bedTransportType['label'])->toBe('Bed Transport')
            ->and($bedTransportType['description'])->toBe('For stretcher and bed transport');
    });

    it('returns correct labels and descriptions for accessibility requirements', function () {
        $response = get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        $accessibilityRequirements = $response->json('data.accessibility_requirements');

        $wheelchairReq = collect($accessibilityRequirements)->firstWhere('id', AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->value);
        expect($wheelchairReq['label'])->toBe('Wheelchair Accessible');
        expect($wheelchairReq['description'])->toBe('Standard Wheelchair transport');

        $oxygenReq = collect($accessibilityRequirements)->firstWhere('id', AccessibilityRequirementsEnum::OXYGEN_SUPPORT->value);
        expect($oxygenReq['label'])->toBe('Oxygen Support');
        expect($oxygenReq['description'])->toBe('Portable oxygen support');

        $rampReq = collect($accessibilityRequirements)->firstWhere('id', AccessibilityRequirementsEnum::PORTABLE_RAMP->value);
        expect($rampReq['label'])->toBe('Portable Ramp');
        expect($rampReq['description'])->toBe('Equipped with ramp access');
    });

    it('does not require authentication', function () {
        get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);
    });

    it('has all required fields in response', function () {
        $response = get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        $data = $response->json('data');

        expect($data)->toHaveKeys([
            'trip_types',
            'vehicle_types',
            'accessibility_requirements',
            'maximum_passengers',
        ]);
    });

    it('returns integer values for enum ids', function () {
        $response = get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        $tripTypes = $response->json('data.trip_types');
        $vehicleTypes = $response->json('data.vehicle_types');
        $accessibilityRequirements = $response->json('data.accessibility_requirements');

        // Check that all IDs are integers
        foreach ($tripTypes as $type) {
            expect($type['id'])->toBeInt();
        }

        foreach ($vehicleTypes as $type) {
            expect($type['id'])->toBeInt();
        }

        foreach ($accessibilityRequirements as $requirement) {
            expect($requirement['id'])->toBeInt();
        }

        expect($response->json('data.maximum_passengers'))->toBeInt();
    });

    it('returns string values for labels and descriptions', function () {
        $response = get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        $tripTypes = $response->json('data.trip_types');
        $vehicleTypes = $response->json('data.vehicle_types');
        $accessibilityRequirements = $response->json('data.accessibility_requirements');

        // Check that all labels are strings
        foreach ($tripTypes as $type) {
            expect($type['label'])->toBeString();
        }

        foreach ($vehicleTypes as $type) {
            expect($type['label'])->toBeString()
                ->and($type['description'])->toBeString();
        }

        foreach ($accessibilityRequirements as $requirement) {
            expect($requirement['label'])->toBeString()
                ->and($requirement['description'])->toBeString();
        }
    });

    it('has consistent data structure across requests', function () {
        $response1 = get(route('v1.customers.trips.form-data'))->assertStatus(200);
        $response2 = get(route('v1.customers.trips.form-data'))->assertStatus(200);

        $data1 = $response1->json('data');
        $data2 = $response2->json('data');

        expect($data1)->toEqual($data2);
    });

    it('returns proper HTTP status codes', function () {
        // Test successful response
        get(route('v1.customers.trips.form-data'))
            ->assertStatus(200);

        // Test 404 for non-existent endpoint
        get('/api/v1/customers/trips/non-existent')
            ->assertStatus(404);
    });
});

describe('Trip Store API', function () {
    beforeEach(function () {
        // Mock the GeocodingService
        $this->mockGeocodingService = Mockery::mock(\App\Services\GeocodingService::class);
        app()->instance(\App\Services\GeocodingService::class, $this->mockGeocodingService);

        // Authenticate a customer
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('can create a trip with valid data', function () {
        // Mock reverse geocoding responses
        $this->mockGeocodingService
            ->shouldReceive('reverseGeocode')
            ->once()
            ->with(29.37694, 47.98306)
            ->andReturn([
                'location_title' => 'Kuwait Hospital',
                'location_sub_title' => 'Sabah medical district',
            ]);

        $this->mockGeocodingService
            ->shouldReceive('reverseGeocode')
            ->once()
            ->with(29.22667, 47.96889)
            ->andReturn([
                'location_title' => 'Kuwait Airport',
                'location_sub_title' => 'Terminal 1',
            ]);

        $response = postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [
                \App\Enums\Trip\AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->value,
            ],
            'passenger_count' => 2,
        ])->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'from' => ['location', 'sub_location', 'lat', 'lng'],
                'to' => ['location', 'sub_location', 'lat', 'lng'],
                'accessibility',
                'ride_types',
                'price_breakdown',
                'price_estimation',
            ],
        ]);

        // Verify database records
        $this->assertDatabaseHas('trips', [
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
        ]);

        $trip = Trip::latest()->first();

        // Verify origin location
        $this->assertDatabaseHas('trip_locations', [
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Kuwait Hospital',
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
        ]);

        // Verify destination location
        $this->assertDatabaseHas('trip_locations', [
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Kuwait Airport',
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
        ]);

        // Verify accessibility
        expect($trip->accessibility)->toHaveCount(1);
    });

    it('validates required fields', function () {
        $response = postJson(route('v1.customers.trips.store'), [])
            ->assertStatus(422);

        // Check that error response has validation errors
        $json = $response->json();
        expect($json)->toHaveKey('meta')
            ->and($json['meta'])->toHaveKey('errors');
    });

    it('validates trip type enum', function () {
        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => 99999,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [],
            'passenger_count' => 2,
        ])->assertStatus(422);
    });

    it('validates vehicle type enum', function () {
        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => 999,
            'accessibility_requirements' => [],
            'passenger_count' => 2,
        ])->assertStatus(422);
    });

    it('validates passenger count range', function () {
        // Test min constraint
        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [],
            'passenger_count' => 0,
        ])->assertStatus(422);

        // Test max constraint
        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [],
            'passenger_count' => 7,
        ])->assertStatus(422);
    });

    it('validates accessibility requirements enum', function () {
        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [999],
            'passenger_count' => 2,
        ])->assertStatus(422);
    });

    it('handles geocoding failures gracefully', function () {
        // Mock reverse geocoding to throw exception
        $this->mockGeocodingService
            ->shouldReceive('reverseGeocode')
            ->once()
            ->andThrow(new \App\Exceptions\GeocodingFailedException());

        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [],
            'passenger_count' => 2,
        ])->assertStatus(422);
    });

    it('can create trip without accessibility requirements', function () {
        // Mock reverse geocoding responses
        $this->mockGeocodingService
            ->shouldReceive('reverseGeocode')
            ->twice()
            ->andReturn([
                'location_title' => 'Kuwait Hospital',
                'location_sub_title' => null,
            ]);

        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [],
            'passenger_count' => 2,
        ])->assertStatus(200);
    });

    it('can create trip with null sub-locations', function () {
        // Mock reverse geocoding responses
        $this->mockGeocodingService
            ->shouldReceive('reverseGeocode')
            ->twice()
            ->andReturn([
                'location_title' => 'Kuwait Hospital',
                'location_sub_title' => null,
            ]);

        postJson(route('v1.customers.trips.store'), [
            'origin_latitude' => 29.37694,
            'origin_longitude' => 47.98306,
            'destination_latitude' => 29.22667,
            'destination_longitude' => 47.96889,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'accessibility_requirements' => [],
            'passenger_count' => 2,
        ])->assertStatus(200);
    });
});

describe('Trip Show API', function () {
    it('can retrieve trip by id', function () {
        $customer = Customer::factory()->create();

        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 2,
            Trip::COLUMN_ACCESSIBILITY_PRICE => null,
            Trip::COLUMN_WAITING_PRICE => null,
            Trip::COLUMN_TOTAL_PRICE => 2.500,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT->value,
        ]);

        // Create origin location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Kuwait Hospital',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Sabah medical district',
            TripLocation::COLUMN_LATITUDE => 29.37694,
            TripLocation::COLUMN_LONGITUDE => 47.98306,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Create destination location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Kuwait Airport',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Terminal 1',
            TripLocation::COLUMN_LATITUDE => 29.22667,
            TripLocation::COLUMN_LONGITUDE => 47.96889,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        \Pest\Laravel\getJson(route('v1.customers.trips.show', $trip->id))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user' => ['id', 'name'],
                    'from' => ['location', 'sub_location', 'lat', 'lng'],
                    'to' => ['location', 'sub_location', 'lat', 'lng'],
                    'accessibility',
                    'ride_type',
                    'vehicle_type',
                    'passenger_count',
                    'payment',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $trip->id,
                    'from' => [
                        'location' => 'Kuwait Hospital',
                        'sub_location' => 'Sabah medical district',
                    ],
                    'to' => [
                        'location' => 'Kuwait Airport',
                        'sub_location' => 'Terminal 1',
                    ],
                ],
            ]);
    })->skip('Show API not implemented');

    it('returns 404 for non-existent trip', function () {
        \Pest\Laravel\getJson(route('v1.customers.trips.show', 99999))
            ->assertStatus(404);
    })->skip('Show API not implemented');
});

describe('Ride Types API', function () {
    it('can list all ride types', function () {
        \Pest\Laravel\getJson(route('v1.customers.ride-types.index'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'label',
                        'description',
                        'icon',
                        'is_default',
                    ],
                ],
            ]);
    })->skip('RideType model and API not implemented yet');

    it('returns all active ride types in correct order', function () {
        $response = \Pest\Laravel\getJson(route('v1.customers.ride-types.index'))
            ->assertStatus(200);

        $rideTypes = $response->json('data');
        expect($rideTypes)->toHaveCount(3);

        // Check ONE_WAY is default
        $oneWay = collect($rideTypes)->firstWhere('key', 'ONE_WAY');
        expect($oneWay['is_default'])->toBeTrue();

        // Check all have required fields
        foreach ($rideTypes as $rideType) {
            expect($rideType)->toHaveKeys([
                'id', 'key', 'label', 'description', 'icon', 'is_default',
            ]);
        }
    })->skip('RideType model and API not implemented yet');

    it('returns translated labels based on locale', function () {
        // Test English (default)
        $response = \Pest\Laravel\getJson(route('v1.customers.ride-types.index'))
            ->assertStatus(200);

        $oneWay = collect($response->json('data'))->firstWhere('key', 'ONE_WAY');
        expect($oneWay['label'])->toBe('One-way ride');

        // Test that Arabic translations exist in the model
        $rideType = \App\Models\RideType::where('key', 'ONE_WAY')->first();
        expect($rideType->label_ar)->toBe('رحلة ذهاب فقط');
    })->skip('RideType model and API not implemented yet');
});

describe('Change Ride Type API', function () {
    beforeEach(function () {
        // Authenticate a customer
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');

        // Create a trip for testing
        $this->trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::SCHEDULED->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::DRAFT->value,
        ]);

        // Create origin location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $this->trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Kuwait City',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Salmiya',
            TripLocation::COLUMN_LATITUDE => 29.37694,
            TripLocation::COLUMN_LONGITUDE => 47.98306,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Create destination location
        TripLocation::create([
            TripLocation::COLUMN_TRIP_ID => $this->trip->id,
            TripLocation::COLUMN_LOCATION_TITLE => 'Ahmadi',
            TripLocation::COLUMN_LOCATION_SUB_TITLE => 'Fahaheel',
            TripLocation::COLUMN_LATITUDE => 29.22667,
            TripLocation::COLUMN_LONGITUDE => 47.96889,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);
    });

    it('can calculate pricing for ONE_WAY ride type', function () {
        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ONE_WAY->value,
        ])->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'price_breakdown' => [
                    '*' => ['label', 'value'],
                ],
                'price_estimation' => ['label', 'value'],
            ],
        ]);

        // Verify ONE_WAY has single base fare
        $breakdown = $response->json('data.price_breakdown');
        expect($breakdown)->toHaveCount(1);
        expect($breakdown[0]['label'])->toBe('Base Fare');

        // Verify waiting_time_config is not present for ONE_WAY
        expect($response->json('data'))->not->toHaveKey('waiting_time_config');
    });

    it('can calculate pricing for ROUND_TRIP ride type', function () {
        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
        ])->assertStatus(200);

        // Verify ROUND_TRIP has base fare and round trip fee
        $breakdown = $response->json('data.price_breakdown');
        expect($breakdown)->toHaveCount(2);
        expect($breakdown[0]['label'])->toBe('Base Fare');
        expect($breakdown[1]['label'])->toContain('Round Trip');

        // Verify waiting_time_config is not present for ROUND_TRIP
        expect($response->json('data'))->not->toHaveKey('waiting_time_config');
    });

    it('can calculate pricing for ROUND_TRIP_WAIT with waiting time', function () {
        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP_WAIT->value,
            'return_time' => 60,
        ])->assertStatus(200);

        // Verify ROUND_TRIP_WAIT has base fare, round trip fee, and waiting time charge
        $breakdown = $response->json('data.price_breakdown');
        expect($breakdown)->toHaveCount(3);
        expect($breakdown[0]['label'])->toBe('Base Fare');
        expect($breakdown[1]['label'])->toContain('Round Trip');
        expect($breakdown[2]['label'])->toContain('Waiting Time');

        // Verify waiting time config is present
        $config = $response->json('data.waiting_time_config');
        expect($config)->toHaveKey('price');
        expect($config)->toHaveKey('time');
        expect($config['time'])->toBe('30 minutes');
    });

    it('can get waiting time config without location data', function () {
        // Skip this test - location data is always required from trip model
    })->skip('Location data is always required from trip model');

    it('can update destination location when changing ride type', function () {
        $newTitle = 'New Airport Terminal';
        $newSubTitle = 'Terminal 3, Gate 5';
        $newLatitude = 29.3117;
        $newLongitude = 47.4818;

        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP->value,
            'destination_location_title' => $newTitle,
            'destination_location_sub_title' => $newSubTitle,
            'destination_latitude' => $newLatitude,
            'destination_longitude' => $newLongitude,
        ])->assertStatus(200);

        // Verify destination location was updated in database
        $this->trip->refresh();
        $this->trip->load('locations');

        $destination = $this->trip->locations
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
            ->first();

        expect($destination->{App\Models\TripLocation::COLUMN_LOCATION_TITLE})->toBe($newTitle);
        expect($destination->{App\Models\TripLocation::COLUMN_LOCATION_SUB_TITLE})->toBe($newSubTitle);
        expect((float)$destination->{App\Models\TripLocation::COLUMN_LATITUDE})->toBe($newLatitude);
        expect((float)$destination->{App\Models\TripLocation::COLUMN_LONGITUDE})->toBe($newLongitude);
    });

    it('validates required fields for change ride type', function () {
        $response = postJson(route('v1.customers.trips.change-ride-type', $this->trip), [])
            ->assertStatus(422);

        // Check that error response has validation errors
        $json = $response->json();
        expect($json)->toHaveKey('meta')
            ->and($json['meta'])->toHaveKey('errors');
    });

    it('validates ride type enum', function () {
        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => 99999,
        ])->assertStatus(422);
    });

    it('validates latitude and longitude ranges when provided', function () {
        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'destination_latitude' => 999,
            'destination_longitude' => 47.96889,
            'ride_type_id' => RideTypeEnum::ONE_WAY->value,
        ])->assertStatus(422);
    });

    it('validates return time range when provided', function () {
        postJson(route('v1.customers.trips.change-ride-type', $this->trip), [
            'ride_type_id' => RideTypeEnum::ROUND_TRIP_WAIT->value,
            'return_time' => 9999,
        ])->assertStatus(422);
    });

    it('requires authentication', function () {
        $trip = Trip::factory()->create();
        postJson(route('v1.customers.trips.change-ride-type', $trip), [
            'ride_type_id' => RideTypeEnum::ONE_WAY->value,
        ])->assertStatus(401);
    })->skip(); // Skip this test as actingAs is set in beforeEach
});

describe('Cancel Trip API', function () {
    beforeEach(function () {
        // Authenticate a customer
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('can cancel a pending trip', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::DRAFT->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip), [
            'cancellation_reason' => 'Changed my mind',
        ])->assertStatus(200);

        // Verify trip status was updated
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);
    });

    it('can cancel a confirmed trip', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip))
            ->assertStatus(200);

        // Verify trip status was updated
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);
    });

    it('cannot cancel a trip with driver assigned', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip))
            ->assertStatus(406)
            ->assertJson([
                'meta' => [
                    'message' => trans('trips.api.exceptions.trip_cannot_be_cancelled'),
                ],
            ]);

        // Verify trip status was NOT updated
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::ACCEPTED_RIDER);
    });

    it('cannot cancel an in-progress trip', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::PICKED_UP->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip))
            ->assertStatus(406);

        // Verify trip status was NOT updated
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::PICKED_UP);
    });

    it('cannot cancel a completed trip', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip))
            ->assertStatus(406);

        // Verify trip status was NOT updated
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::COMPLETED);
    });

    it('cannot cancel an already cancelled trip', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip))
            ->assertStatus(406);

        // Verify trip status remains cancelled
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);
    });

    it('validates cancellation reason length', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::DRAFT->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip), [
            'cancellation_reason' => str_repeat('a', 501),
        ])->assertStatus(422);
    });

    it('can cancel trip without providing a reason', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::DRAFT->value,
        ]);

        postJson(route('v1.customers.trips.cancel', $trip))
            ->assertStatus(200);

        // Verify trip status was updated
        $trip->refresh();
        expect($trip->status)->toBe(TripStatusEnum::CANCELED_BY_CUSTOMER);
    });
});

describe('Get Trip Status API', function () {
    beforeEach(function () {
        // Authenticate a customer
        $this->customer = Customer::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($this->customer, ['*'], 'customer');
    });

    it('can get trip status for valid statuses', function () {
        // Create trip with ACCEPTED_RIDER status
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        \Pest\Laravel\getJson(route('v1.customers.trips.status', $trip))
            ->assertStatus(200);
    });

    it('cannot check status for draft trips', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::DRAFT->value,
        ]);

        \Pest\Laravel\getJson(route('v1.customers.trips.status', $trip))
            ->assertStatus(406);
    });

    it('cannot check status for cancelled by customer trips', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
        ]);

        \Pest\Laravel\getJson(route('v1.customers.trips.status', $trip))
            ->assertStatus(406);
    });

    it('cannot check status for cancelled by rider trips', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::CANCELLED_BY_RIDER->value,
        ]);

        \Pest\Laravel\getJson(route('v1.customers.trips.status', $trip))
            ->assertStatus(406);
    });

    it('cannot check status for completed trips', function () {
        $trip = Trip::create([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 2,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        \Pest\Laravel\getJson(route('v1.customers.trips.status', $trip))
            ->assertStatus(406);
    });
});
