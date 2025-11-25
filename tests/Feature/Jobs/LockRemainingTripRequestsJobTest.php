<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Jobs\Trip\LockRemainingTripRequestsJob;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
    $this->rider1 = Rider::factory()->create();
    $this->rider2 = Rider::factory()->create();
    $this->rider3 = Rider::factory()->create();

    // Create a trip
    $this->trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER->value,
    ]);
});

test('it locks all pending trip requests except the accepted one', function () {
    // Create multiple trip requests for the same trip
    $acceptedRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider1->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1000,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    $pendingRequest1 = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider2->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1200,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 350,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    $pendingRequest2 = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider3->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1500,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 400,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    // Dispatch the job
    LockRemainingTripRequestsJob::dispatchSync(
        $this->trip->{Trip::COLUMN_ID},
        $acceptedRequest->{TripRequest::COLUMN_ID}
    );

    // Assert accepted request remains accepted
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $acceptedRequest->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
    ]);

    // Assert pending requests are now locked
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $pendingRequest1->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::LOCKED->value,
    ]);

    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $pendingRequest2->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::LOCKED->value,
    ]);

    // Assert responded_at is set for locked requests
    $pendingRequest1->refresh();
    $pendingRequest2->refresh();

    expect($pendingRequest1->{TripRequest::COLUMN_RESPONDED_AT})->not->toBeNull();
    expect($pendingRequest2->{TripRequest::COLUMN_RESPONDED_AT})->not->toBeNull();
});

test('it does not lock already declined trip requests', function () {
    $acceptedRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider1->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1000,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    $declinedRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider2->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1200,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 350,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::DECLINED->value,
        TripRequest::COLUMN_SENT_AT => now(),
        TripRequest::COLUMN_RESPONDED_AT => now(),
    ]);

    // Dispatch the job
    LockRemainingTripRequestsJob::dispatchSync(
        $this->trip->{Trip::COLUMN_ID},
        $acceptedRequest->{TripRequest::COLUMN_ID}
    );

    // Assert declined request remains declined
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $declinedRequest->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::DECLINED->value,
    ]);
});

test('it does not lock expired trip requests', function () {
    $acceptedRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider1->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1000,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    $expiredRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider2->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1200,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 350,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::EXPIRED->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    // Dispatch the job
    LockRemainingTripRequestsJob::dispatchSync(
        $this->trip->{Trip::COLUMN_ID},
        $acceptedRequest->{TripRequest::COLUMN_ID}
    );

    // Assert expired request remains expired
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $expiredRequest->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::EXPIRED->value,
    ]);
});

test('it only locks trip requests for the specific trip', function () {
    // Create another trip
    $anotherTrip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $this->customer->{Customer::COLUMN_ID},
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER->value,
    ]);

    $acceptedRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider1->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1000,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    $pendingRequestForSameTrip = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider2->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1200,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 350,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    $pendingRequestForAnotherTrip = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $anotherTrip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider3->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1500,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 400,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    // Dispatch the job for the first trip
    LockRemainingTripRequestsJob::dispatchSync(
        $this->trip->{Trip::COLUMN_ID},
        $acceptedRequest->{TripRequest::COLUMN_ID}
    );

    // Assert pending request for the same trip is locked
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $pendingRequestForSameTrip->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::LOCKED->value,
    ]);

    // Assert pending request for another trip remains pending
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $pendingRequestForAnotherTrip->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
    ]);
});

test('it handles case when there are no other pending requests', function () {
    $acceptedRequest = TripRequest::create([
        TripRequest::COLUMN_TRIP_ID => $this->trip->{Trip::COLUMN_ID},
        TripRequest::COLUMN_RIDER_ID => $this->rider1->{Rider::COLUMN_ID},
        TripRequest::COLUMN_DISTANCE_METERS => 1000,
        TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => 300,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    // Dispatch the job (should not throw any errors)
    LockRemainingTripRequestsJob::dispatchSync(
        $this->trip->{Trip::COLUMN_ID},
        $acceptedRequest->{TripRequest::COLUMN_ID}
    );

    // Assert accepted request remains accepted
    assertDatabaseHas('trip_requests', [
        TripRequest::COLUMN_ID => $acceptedRequest->{TripRequest::COLUMN_ID},
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED->value,
    ]);

    // Assert no other requests were created or modified
    expect(TripRequest::count())->toBe(1);
});
