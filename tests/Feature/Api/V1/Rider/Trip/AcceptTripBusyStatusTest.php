<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);

    $this->customer = Customer::factory()->create();

    $this->token = $this->rider->createToken('test')->plainTextToken;
    $this->headers = [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'application/json',
        'Language' => 'en',
    ];

    // Helper function to create a trip with trip request
    $this->createTrip = function (array $tripOverrides = [], array $tripRequestOverrides = []) {
        $trip = Trip::create(array_merge([
            'customer_id' => $this->customer->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ], $tripOverrides));

        $tripRequest = TripRequest::create(array_merge([
            'trip_id' => $trip->id,
            'rider_id' => $this->rider->id,
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
            'status' => TripRequestStatusEnum::PENDING->value,
            'sent_at' => now(),
        ], $tripRequestOverrides));

        $trip->tripRequest = $tripRequest;

        return $trip;
    };
});

describe('V1 Rider Trip API → Accept Trip → Rider Status', function () {
    test('rider status becomes BUSY after accepting a trip', function () {
        // Create a trip with pending status
        $trip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Accept the trip
        $response = postJson(
            apiUrl("/v1/riders/trips/requests/{$trip->tripRequest->id}/accept"),
            [],
            $this->headers
        );

        $response->assertOk();

        // Verify rider status is now BUSY
        $this->rider->refresh();
        expect($this->rider->{Rider::COLUMN_STATUS})->toBe(RiderStatusEnum::BUSY);
    });

    test('rider cannot accept a second trip while having an active trip', function () {
        // Create first trip and accept it
        $firstTrip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Accept first trip
        postJson(
            apiUrl("/v1/riders/trips/requests/{$firstTrip->tripRequest->id}/accept"),
            [],
            $this->headers
        );

        // Verify rider is now busy
        $this->rider->refresh();
        expect($this->rider->{Rider::COLUMN_STATUS})->toBe(RiderStatusEnum::BUSY);

        // Create second trip
        $secondTrip = ($this->createTrip)([
            'status' => TripStatusEnum::PENDING_RIDER->value,
        ]);

        // Try to accept second trip - should fail
        $response = postJson(
            apiUrl("/v1/riders/trips/requests/{$secondTrip->tripRequest->id}/accept"),
            [],
            $this->headers
        );

        $response->assertStatus(406);
        $response->assertJson([
            'meta' => [
                'message' => __('riders.api.errors.rider_not_available'),
            ],
        ]);

        // Verify second trip request is still pending
        $secondTrip->tripRequest->refresh();
        expect($secondTrip->tripRequest->status)->toBe(TripRequestStatusEnum::PENDING);
    });
});
