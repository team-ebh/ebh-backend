<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\RiderStatusLog;
use App\Models\Trip;

use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        'status' => RiderStatusEnum::ONLINE,
    ]);

    $this->headers = [
        'Authorization' => 'Bearer ' . $this->rider->createToken('test')->plainTextToken,
    ];
});

describe('Update Rider Status API', function () {
    it('can change status from online to offline', function () {
        expect($this->rider->status)->toBe(RiderStatusEnum::ONLINE);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertOk();

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::OFFLINE);
    });

    it('can change status from offline to online', function () {
        $this->rider->update(['status' => RiderStatusEnum::OFFLINE]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::ONLINE->value,
        ], $this->headers)
            ->assertOk();

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::ONLINE);
    });

    it('cannot change status when rider is busy', function () {
        $this->rider->update(['status' => RiderStatusEnum::BUSY]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertStatus(406);

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::BUSY);
    });

    it('cannot change status when rider has active trip', function () {
        $customer = Customer::factory()->create();

        // Create an active trip for this rider
        Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $this->rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::ACCEPTED_RIDER->value,
        ]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertStatus(406);

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::ONLINE);
    });

    it('can change status when previous trip is completed', function () {
        $customer = Customer::factory()->create();

        // Create a completed trip
        Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $this->rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::COMPLETED->value,
        ]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertOk();

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::OFFLINE);
    });

    it('validates required status field', function () {
        $response = putJson(route('v1.riders.status.update'), [], $this->headers)
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('status');
    });

    it('validates status must be online or offline', function () {
        $response = putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::BUSY->value,
        ], $this->headers)
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('status');
    });

    it('validates status must be a valid enum value', function () {
        $response = putJson(route('v1.riders.status.update'), [
            'status' => 999,
        ], $this->headers)
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('status');
    });

    it('requires authentication', function () {
        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ])
            ->assertStatus(401);
    });

    it('cannot change status when has pending rider trip', function () {
        $customer = Customer::factory()->create();

        Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $this->rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertStatus(406);

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::ONLINE);
    });

    it('cannot change status when trip is on trip', function () {
        $customer = Customer::factory()->create();

        Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $this->rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::ON_TRIP->value,
        ]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertStatus(406);

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::ONLINE);
    });

    it('can change status when all trips are cancelled', function () {
        $customer = Customer::factory()->create();

        Trip::create([
            'customer_id' => $customer->id,
            'rider_id' => $this->rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => 'KWD',
            'status' => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
        ]);

        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertOk();

        $this->rider->refresh();
        expect($this->rider->status)->toBe(RiderStatusEnum::OFFLINE);
    });

    it('creates status log when status changes via API', function () {
        // Initial status log count (1 from rider creation)
        $initialLogCount = RiderStatusLog::query()
            ->where(RiderStatusLog::COLUMN_RIDER_ID, $this->rider->{Rider::COLUMN_ID})
            ->count();

        expect($initialLogCount)->toBe(1);

        // Change status from ONLINE to OFFLINE
        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::OFFLINE->value,
        ], $this->headers)
            ->assertOk();

        // Verify new log was created
        $afterFirstChangeLogCount = RiderStatusLog::query()
            ->where(RiderStatusLog::COLUMN_RIDER_ID, $this->rider->{Rider::COLUMN_ID})
            ->count();

        expect($afterFirstChangeLogCount)->toBe(2);

        // Verify the latest log has the correct status
        $latestLog = RiderStatusLog::query()
            ->where(RiderStatusLog::COLUMN_RIDER_ID, $this->rider->{Rider::COLUMN_ID})
            ->latest('id')
            ->first();

        expect($latestLog->{RiderStatusLog::COLUMN_STATUS})->toBe(RiderStatusEnum::OFFLINE);

        // Change status from OFFLINE to ONLINE
        putJson(route('v1.riders.status.update'), [
            'status' => RiderStatusEnum::ONLINE->value,
        ], $this->headers)
            ->assertOk();

        // Verify another log was created
        $finalLogCount = RiderStatusLog::query()
            ->where(RiderStatusLog::COLUMN_RIDER_ID, $this->rider->{Rider::COLUMN_ID})
            ->count();

        expect($finalLogCount)->toBe(3);

        // Verify the latest log has the correct status
        $latestLog = RiderStatusLog::query()
            ->where(RiderStatusLog::COLUMN_RIDER_ID, $this->rider->{Rider::COLUMN_ID})
            ->latest('id')
            ->first();

        expect($latestLog->{RiderStatusLog::COLUMN_STATUS})->toBe(RiderStatusEnum::ONLINE);
        expect($latestLog->{RiderStatusLog::COLUMN_CHANGED_BY_TYPE})->toBe($this->rider->getMorphClass());
        expect($latestLog->{RiderStatusLog::COLUMN_CHANGED_BY_ID})->toBe($this->rider->{Rider::COLUMN_ID});
    });
});
