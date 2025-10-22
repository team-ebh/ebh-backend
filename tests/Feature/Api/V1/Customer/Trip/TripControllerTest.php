<?php

declare(strict_types=1);

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use function Pest\Laravel\{get, withHeaders, getJson, actingAs};

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
