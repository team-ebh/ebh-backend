<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use App\Models\TripRequestStatusLog;
use App\Models\TripStatusLog;
use App\Models\RiderStatusLog;

test('RiderObserver creates status log on rider creation', function () {
    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    $rider = Rider::query()->create([
        Rider::COLUMN_FULL_NAME => 'Test Rider',
        Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
        Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Rider::COLUMN_COMPANY_ID => $company->id,
        Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
    ]);

    // Verify log was created
    $logCount = RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->count();

    expect($logCount)->toBe(1);

    $log = RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->first();

    expect($log->{RiderStatusLog::COLUMN_STATUS})->toBe(RiderStatusEnum::OFFLINE);
});

test('RiderObserver creates status log when rider status changes', function () {
    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    $rider = Rider::query()->create([
        Rider::COLUMN_FULL_NAME => 'Test Rider',
        Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
        Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Rider::COLUMN_COMPANY_ID => $company->id,
        Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
    ]);

    // Initial log should exist
    expect(RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->count())->toBe(1);

    // Change status to ONLINE
    $rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);

    // Should have 2 logs now
    $logCount = RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->count();

    expect($logCount)->toBe(2);

    // Verify latest log has ONLINE status
    $latestLog = RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->latest('id')
        ->first();

    expect($latestLog->{RiderStatusLog::COLUMN_STATUS})->toBe(RiderStatusEnum::ONLINE);

    // Change status to BUSY
    $rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::BUSY]);

    // Should have 3 logs now
    expect(RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->count())->toBe(3);

    // Verify latest log has BUSY status
    $latestLog = RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->latest('id')
        ->first();

    expect($latestLog->{RiderStatusLog::COLUMN_STATUS})->toBe(RiderStatusEnum::BUSY);
});

test('RiderObserver does not create log when status does not change', function () {
    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    $rider = Rider::query()->create([
        Rider::COLUMN_FULL_NAME => 'Test Rider',
        Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
        Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Rider::COLUMN_COMPANY_ID => $company->id,
        Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
    ]);

    // Should have 1 log
    expect(RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->count())->toBe(1);

    // Update other fields but not status
    $rider->update([Rider::COLUMN_FULL_NAME => 'Updated Rider Name']);

    // Should still have only 1 log
    expect(RiderStatusLog::query()
        ->where(RiderStatusLog::COLUMN_RIDER_ID, $rider->{Rider::COLUMN_ID})
        ->count())->toBe(1);
});

test('TripObserver creates status log on trip creation', function () {
    $customer = Customer::query()->create([
        Customer::COLUMN_FIRST_NAME => 'Test',
        Customer::COLUMN_LAST_NAME => 'Customer',
        Customer::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
        Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    // Verify log was created
    $logCount = TripStatusLog::query()
        ->where(TripStatusLog::COLUMN_TRIP_ID, $trip->{Trip::COLUMN_ID})
        ->count();

    expect($logCount)->toBe(1);

    $log = TripStatusLog::query()
        ->where(TripStatusLog::COLUMN_TRIP_ID, $trip->{Trip::COLUMN_ID})
        ->first();

    expect($log->{TripStatusLog::COLUMN_STATUS})->toBe(TripStatusEnum::DRAFT);
});

test('TripRequestObserver creates status log on trip request creation', function () {
    $customer = Customer::query()->create([
        Customer::COLUMN_FIRST_NAME => 'Test',
        Customer::COLUMN_LAST_NAME => 'Customer',
        Customer::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
        Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    $rider = Rider::query()->create([
        Rider::COLUMN_FULL_NAME => 'Test Rider',
        Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
        Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Rider::COLUMN_COMPANY_ID => $company->id,
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);

    $tripRequest = TripRequest::query()->create([
        TripRequest::COLUMN_TRIP_ID => $trip->id,
        TripRequest::COLUMN_RIDER_ID => $rider->id,
        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING,
        TripRequest::COLUMN_SENT_AT => now(),
    ]);

    // Verify log was created
    $logCount = TripRequestStatusLog::query()
        ->where(TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID, $tripRequest->{TripRequest::COLUMN_ID})
        ->count();

    expect($logCount)->toBe(1);

    $log = TripRequestStatusLog::query()
        ->where(TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID, $tripRequest->{TripRequest::COLUMN_ID})
        ->first();

    expect($log->{TripRequestStatusLog::COLUMN_STATUS})->toBe(TripRequestStatusEnum::PENDING);
});

test('TripRequestRepository bulk insert creates status logs', function () {
    $customer = Customer::query()->create([
        Customer::COLUMN_FIRST_NAME => 'Test',
        Customer::COLUMN_LAST_NAME => 'Customer',
        Customer::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    $trip = Trip::query()->create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
        Trip::COLUMN_PASSENGER_COUNT => 1,
    ]);

    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    // Create 3 riders
    $riderIds = [];
    for ($i = 0; $i < 3; $i++) {
        $rider = Rider::query()->create([
            Rider::COLUMN_FULL_NAME => "Test Rider {$i}",
            Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
            Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
            Rider::COLUMN_COMPANY_ID => $company->id,
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
        $riderIds[] = $rider->id;
    }

    $riders = Rider::query()->whereIn('id', $riderIds)->get();

    // Use repository to bulk insert
    $repository = app(\App\Interfaces\Repositories\TripRequestRepositoryInterface::class);
    $repository->createForRiders($trip, $riders, 1, 5000);

    // Verify all trip requests were created
    $tripRequestCount = TripRequest::query()
        ->where(TripRequest::COLUMN_TRIP_ID, $trip->id)
        ->count();

    expect($tripRequestCount)->toBe(3);

    // Verify all status logs were created
    $tripRequestIds = TripRequest::query()
        ->where(TripRequest::COLUMN_TRIP_ID, $trip->id)
        ->pluck(TripRequest::COLUMN_ID);

    $logCount = TripRequestStatusLog::query()
        ->whereIn(TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID, $tripRequestIds)
        ->count();

    expect($logCount)->toBe(3);

    // Verify all logs have correct status
    $logs = TripRequestStatusLog::query()
        ->whereIn(TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID, $tripRequestIds)
        ->get();

    $logs->each(function ($log) {
        expect($log->{TripRequestStatusLog::COLUMN_STATUS})->toBe(TripRequestStatusEnum::PENDING);
    });
});
