<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripAccessibility;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, float $baseFare, float $estimatedPrice): Trip
    {
        $trip = new Trip();
        $trip->{Trip::COLUMN_CUSTOMER_ID} = $dto->customerId;
        $trip->{Trip::COLUMN_ORIGIN_LOCATION} = $originLocation['location_title'];
        $trip->{Trip::COLUMN_ORIGIN_SUB_LOCATION} = $originLocation['location_sub_title'];
        $trip->{Trip::COLUMN_ORIGIN_LATITUDE} = $dto->originLatitude;
        $trip->{Trip::COLUMN_ORIGIN_LONGITUDE} = $dto->originLongitude;
        $trip->{Trip::COLUMN_DESTINATION_LOCATION} = $destinationLocation['location_title'];
        $trip->{Trip::COLUMN_DESTINATION_SUB_LOCATION} = $destinationLocation['location_sub_title'];
        $trip->{Trip::COLUMN_DESTINATION_LATITUDE} = $dto->destinationLatitude;
        $trip->{Trip::COLUMN_DESTINATION_LONGITUDE} = $dto->destinationLongitude;
        $trip->{Trip::COLUMN_TRIP_TYPE_ID} = $dto->tripTypeId;
        $trip->{Trip::COLUMN_VEHICLE_TYPE_ID} = $dto->vehicleTypeId;
        $trip->{Trip::COLUMN_PASSENGER_COUNT} = $dto->passengerCount;
        $trip->{Trip::COLUMN_BASE_FARE} = $baseFare;
        $trip->{Trip::COLUMN_ESTIMATED_PRICE} = $estimatedPrice;
        $trip->{Trip::COLUMN_CURRENCY} = CurrencyEnum::KWD;
        $trip->{Trip::COLUMN_STATUS} = TripStatusEnum::PENDING;
        $trip->{Trip::COLUMN_ID} = rand(1, 5); // TODO must be remove
        //        $trip->save();

        return $trip;
    }

    public function attachAccessibilityRequirements(Trip $trip, array $requirements): void
    {
        foreach ($requirements as $requirement) {
            $tripAccessibility = new TripAccessibility();
            $tripAccessibility->{TripAccessibility::COLUMN_TRIP_ID} = $trip->{Trip::COLUMN_ID};
            $tripAccessibility->{TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT} = $requirement;
        } // TODO must be remove

        //        $data = array_map(
        //            fn ($requirement) => [
        //                TripAccessibility::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
        //                TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => $requirement,
        //                'created_at' => now(),
        //                'updated_at' => now(),
        //            ],
        //            $requirements
        //        );
        //
        //        TripAccessibility::insert($data);
    }

    public function findById(int $id): ?Trip
    {
        return Trip::query()
            ->with('customer')
            ->find($id);
    }

    public function getCustomerTrips(int $customerId): Collection
    {
        return Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->with('customer')
            ->orderByDesc(Trip::COLUMN_ID)
            ->get();
    }

    public function updateStatus(Trip $trip, string $status): Trip
    {
        $trip->update([
            Trip::COLUMN_STATUS => $status,
        ]);

        return $trip->fresh();
    }
}
