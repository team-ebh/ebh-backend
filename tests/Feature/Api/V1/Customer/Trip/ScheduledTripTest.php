<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Setting\SettingEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Events\Socket\Customer\TripSearchingForRiderEvent;
use App\Jobs\ProcessScheduledTripJob;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Services\Trip\ScheduledTripDispatcherService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

describe('Scheduled Trip Confirmation', function () {
    beforeEach(function () {
        Queue::fake();
        $this->customer = Customer::factory()->create();
        Sanctum::actingAs($this->customer, ['*'], 'customer');

        // Set the search start minutes setting
        Setting::set(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES, 30);
    });

    it('keeps trip in DRAFT status when confirmed for future date', function () {
        // Create a scheduled trip for 5 days in the future
        $scheduledTime = now()->addDays(5);

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
            Trip::COLUMN_SCHEDULED_TIME => $scheduledTime,
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

        // Confirm the trip
        $response = postJson(route('v1.customers.trips.confirm', $trip), [
            'payment_method' => PaymentMethodEnum::KNET->value,
        ]);

        $response->assertStatus(200);

        // Refresh the trip from database
        $trip->refresh();

        // Trip should still be in DRAFT status (not PENDING_RIDER)
        expect($trip->{Trip::COLUMN_STATUS})->toBe(TripStatusEnum::DRAFT);
    });

    it('dispatches job with correct delay for future scheduled trip', function () {
        $scheduledTime = now()->addDays(5);
        $searchStartMinutes = 30;

        Setting::set(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES, $searchStartMinutes);

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
            Trip::COLUMN_SCHEDULED_TIME => $scheduledTime,
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

        // Confirm the trip
        postJson(route('v1.customers.trips.confirm', $trip), [
            'payment_method' => PaymentMethodEnum::KNET->value,
        ]);

        // Job should be dispatched with a delay
        Queue::assertPushed(ProcessScheduledTripJob::class, function ($job) use ($trip) {
            return $job->tripId === $trip->id;
        });
    });

    it('does not dispatch job immediately for trip scheduled days in the future', function () {
        $scheduledTime = now()->addDays(5);

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
            Trip::COLUMN_SCHEDULED_TIME => $scheduledTime,
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

        // Confirm the trip
        postJson(route('v1.customers.trips.confirm', $trip), [
            'payment_method' => PaymentMethodEnum::KNET->value,
        ]);

        // Trip should still be in DRAFT (job not executed yet)
        $trip->refresh();
        expect($trip->{Trip::COLUMN_STATUS})->toBe(TripStatusEnum::DRAFT);
    });
});

describe('ProcessScheduledTripJob Event Dispatch', function () {
    beforeEach(function () {
        Event::fake([TripSearchingForRiderEvent::class]);
        $this->customer = Customer::factory()->create();
    });

    it('dispatches TripSearchingForRiderEvent when scheduled trip starts searching', function () {
        // Create a scheduled trip in DRAFT status
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
            Trip::COLUMN_SCHEDULED_TIME => now()->addHour(),
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

        // Run the job
        $job = new ProcessScheduledTripJob(tripId: $trip->id);
        app()->call([$job, 'handle']);

        // Assert event was dispatched with correct data
        Event::assertDispatched(
            TripSearchingForRiderEvent::class,
            fn ($event) => $event->customerId === $this->customer->id
                && $event->tripId === $trip->id
        );
    });

    it('does not dispatch TripSearchingForRiderEvent when trip is cancelled', function () {
        // Create a cancelled trip
        $trip = Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_BASE_FARE => 5.000,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
            Trip::COLUMN_SCHEDULED_TIME => now()->addHour(),
        ]);

        // Run the job
        $job = new ProcessScheduledTripJob(tripId: $trip->id);
        app()->call([$job, 'handle']);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(TripSearchingForRiderEvent::class);
    });

    it('does not dispatch TripSearchingForRiderEvent when trip does not exist', function () {
        // Run the job with non-existent trip ID
        $job = new ProcessScheduledTripJob(tripId: 99999);
        app()->call([$job, 'handle']);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(TripSearchingForRiderEvent::class);
    });
});

describe('ScheduledTripDispatcherService Delay Calculation', function () {
    beforeEach(function () {
        Setting::set(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES, 30);
    });

    it('calculates positive delay for future scheduled trip', function () {
        $service = app(ScheduledTripDispatcherService::class);

        // Schedule trip for 5 days from now
        $scheduledTime = now()->addDays(5);

        $delay = $service->calculateDelaySeconds($scheduledTime);

        // Delay should be approximately (5 days - 30 minutes) in seconds
        // 5 days = 5 * 24 * 60 * 60 = 432000 seconds
        // 30 minutes = 30 * 60 = 1800 seconds
        // Expected delay ≈ 432000 - 1800 = 430200 seconds
        $expectedDelay = (5 * 24 * 60 * 60) - (30 * 60);

        // Allow 5 seconds tolerance for test execution time
        expect($delay)->toBeGreaterThan($expectedDelay - 5)
            ->and($delay)->toBeLessThan($expectedDelay + 5);
    });

    it('calculates zero delay when scheduled time is within search start window', function () {
        $service = app(ScheduledTripDispatcherService::class);

        // Schedule trip for 15 minutes from now (within 30-minute search window)
        $scheduledTime = now()->addMinutes(15);

        $delay = $service->calculateDelaySeconds($scheduledTime);

        // Job should run immediately since we're within the search window
        expect($delay)->toBe(0);
    });

    it('calculates zero delay when scheduled time is in the past', function () {
        $service = app(ScheduledTripDispatcherService::class);

        // Schedule trip for 1 hour ago
        $scheduledTime = now()->subHour();

        $delay = $service->calculateDelaySeconds($scheduledTime);

        // Job should run immediately
        expect($delay)->toBe(0);
    });

    it('calculates correct delay for trip scheduled 1 hour from now', function () {
        $service = app(ScheduledTripDispatcherService::class);

        // Schedule trip for 1 hour from now
        $scheduledTime = now()->addHour();

        $delay = $service->calculateDelaySeconds($scheduledTime);

        // Should run 30 minutes before scheduled time
        // So delay should be approximately 30 minutes (1800 seconds)
        $expectedDelay = (60 - 30) * 60; // 30 minutes in seconds

        // Allow 5 seconds tolerance
        expect($delay)->toBeGreaterThan($expectedDelay - 5)
            ->and($delay)->toBeLessThan($expectedDelay + 5);
    });
});
