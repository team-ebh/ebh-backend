<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, float $accessibilityCost, float $totalPrice): Trip
    {
        $trip = new Trip();
        $trip->{Trip::COLUMN_CUSTOMER_ID} = $dto->customerId;
        $trip->{Trip::COLUMN_TRIP_TYPE_ID} = $dto->tripTypeId;
        $trip->{Trip::COLUMN_VEHICLE_TYPE_ID} = $dto->vehicleTypeId;
        $trip->{Trip::COLUMN_PASSENGER_COUNT} = $dto->passengerCount;
        $trip->{Trip::COLUMN_ACCESSIBILITY_PRICE} = $accessibilityCost > 0 ? $accessibilityCost : null;
        $trip->{Trip::COLUMN_WAITING_PRICE} = null;
        $trip->{Trip::COLUMN_TOTAL_PRICE} = $totalPrice;
        $trip->{Trip::COLUMN_CURRENCY} = CurrencyEnum::KWD;
        $trip->{Trip::COLUMN_STATUS} = TripStatusEnum::PENDING;
        $trip->save();

        // Create origin location
        $originLocationModel = new TripLocation();
        $originLocationModel->{TripLocation::COLUMN_TRIP_ID} = $trip->{Trip::COLUMN_ID};
        $originLocationModel->{TripLocation::COLUMN_LOCATION_TITLE} = $originLocation['location_title'];
        $originLocationModel->{TripLocation::COLUMN_LOCATION_SUB_TITLE} = $originLocation['location_sub_title'];
        $originLocationModel->{TripLocation::COLUMN_LATITUDE} = $dto->originLatitude;
        $originLocationModel->{TripLocation::COLUMN_LONGITUDE} = $dto->originLongitude;
        $originLocationModel->{TripLocation::COLUMN_TYPE} = TripLocationTypeEnum::ORIGIN;
        $originLocationModel->{TripLocation::COLUMN_SEQUENCE} = 1;
        $originLocationModel->save();

        // Create destination location
        $destinationLocationModel = new TripLocation();
        $destinationLocationModel->{TripLocation::COLUMN_TRIP_ID} = $trip->{Trip::COLUMN_ID};
        $destinationLocationModel->{TripLocation::COLUMN_LOCATION_TITLE} = $destinationLocation['location_title'];
        $destinationLocationModel->{TripLocation::COLUMN_LOCATION_SUB_TITLE} = $destinationLocation['location_sub_title'];
        $destinationLocationModel->{TripLocation::COLUMN_LATITUDE} = $dto->destinationLatitude;
        $destinationLocationModel->{TripLocation::COLUMN_LONGITUDE} = $dto->destinationLongitude;
        $destinationLocationModel->{TripLocation::COLUMN_TYPE} = TripLocationTypeEnum::DESTINATION;
        $destinationLocationModel->{TripLocation::COLUMN_SEQUENCE} = 2;
        $destinationLocationModel->save();

        return $trip->fresh(['locations']);
    }

    public function attachAccessibilityRequirements(Trip $trip, array $requirements): void
    {
        $data = array_map(
            fn ($requirement) => [
                TripAccessibility::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => $requirement,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $requirements
        );

        TripAccessibility::insert($data);
    }

    public function findById(int $id): ?Trip
    {
        return Trip::query()
            ->with(['customer', 'locations', 'accessibility'])
            ->find($id);
    }

    public function getCustomerTrips(int $customerId): Collection
    {
        return Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->with(['customer', 'locations', 'accessibility'])
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
