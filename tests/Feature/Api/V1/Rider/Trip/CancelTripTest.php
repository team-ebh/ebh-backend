<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Events\Socket\Customer\TripCancelledByRiderEvent;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create();
    $this->customer = Customer::factory()->create();

    // Helper function to create a trip with trip request
    $this->createTrip = function (array $tripOverrides = [], array $tripRequestOverrides = []) {
        $trip = Trip::create(array_merge([
            'customer_id' => $this->customer->id,
            'rider_id' => $this->rider->id,
            'trip_type_id' => TripTypeEnum::RIDE_NOW->value,
            'vehicle_type_id' => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            'passenger_count' => 1,
            'accessibility_price' => null,
            'waiting_price' => null,
            'total_price' => 5.000,
            'currency' => CurrencyEnum::KWD->value,
            'status' => TripStatusEnum::ACCEPTED_RIDER->value,
        ], $tripOverrides));

        $tripRequest = TripRequest::create(array_merge([
            'trip_id' => $trip->id,
            'rider_id' => $this->rider->id,
            'distance_meters' => 1000,
            'estimated_arrival_seconds' => 300,
            'status' => TripRequestStatusEnum::ACCEPTED->value,
            'sent_at' => now(),
        ], $tripRequestOverrides));

        $trip->tripRequest = $tripRequest;

        return $trip;
    };
});

test('rider can cancel accepted trip successfully', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::ACCEPTED_RIDER->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertOk();

    assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => TripStatusEnum::CANCELLED_BY_RIDER->value,
    ]);

    assertDatabaseHas('trip_requests', [
        'id' => $trip->tripRequest->id,
        'status' => TripRequestStatusEnum::CANCELLED->value,
    ]);
});

test('rider cannot cancel trip that is on trip', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::IN_PROGRESS->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnprocessable();
});

test('rider can cancel trip', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::ACCEPTED_RIDER->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertOk();
});

test('rider cannot cancel trip that is not assigned to them', function () {
    $otherRider = Rider::factory()->create();

    $trip = ($this->createTrip)(
        ['rider_id' => $otherRider->id, 'status' => TripStatusEnum::ACCEPTED_RIDER->value],
        ['rider_id' => $otherRider->id]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertForbidden();
});

test('rider cannot cancel trip when trip is not assigned to rider', function () {
    // Create a trip request for this rider but trip is assigned to another rider
    $otherRider = Rider::factory()->create();

    $trip = ($this->createTrip)(
        ['rider_id' => $otherRider->id, 'status' => TripStatusEnum::ACCEPTED_RIDER->value],
        ['rider_id' => $this->rider->id] // TripRequest belongs to this rider but Trip is assigned to other rider
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertForbidden();
});

test('rider cannot cancel trip with invalid status - draft', function () {
    $trip = ($this->createTrip)(
        ['status' => TripStatusEnum::DRAFT->value],
        ['status' => TripRequestStatusEnum::PENDING->value]
    );

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnprocessable();
});

test('rider cannot cancel trip with invalid status - on trip', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::IN_PROGRESS->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnprocessable();
});

test('rider cannot cancel trip with invalid status - completed', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::COMPLETED->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnprocessable();
});

test('rider cannot cancel trip with invalid status - already cancelled by customer', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnprocessable();
});

test('rider cannot cancel trip with invalid status - already cancelled by rider', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::CANCELLED_BY_RIDER->value,
    ]);

    $response = actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnprocessable();
});

test('unauthenticated rider cannot cancel trip', function () {
    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::ACCEPTED_RIDER->value,
    ]);

    $response = postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel");

    $response->assertUnauthorized();
});

test('trip cancelled by rider event is dispatched when rider cancels trip', function () {
    Event::fake([TripCancelledByRiderEvent::class]);

    $trip = ($this->createTrip)([
        'status' => TripStatusEnum::ACCEPTED_RIDER->value,
    ]);

    actingAs($this->rider, 'rider')
        ->postJson("http://api.localhost/v1/riders/trips/requests/{$trip->tripRequest->id}/cancel")
        ->assertOk();

    Event::assertDispatched(TripCancelledByRiderEvent::class, function ($event) use ($trip) {
        return $event->customerId === $trip->customer_id
            && $event->tripId === $trip->id
            && $event->riderId === $this->rider->id;
    });
});
