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
        // Create trip
        $trip = Trip::query()->create([
            Trip::COLUMN_CUSTOMER_ID => $dto->customerId,
            Trip::COLUMN_TRIP_TYPE_ID => $dto->tripTypeId,
            Trip::COLUMN_VEHICLE_TYPE_ID => $dto->vehicleTypeId,
            Trip::COLUMN_PASSENGER_COUNT => $dto->passengerCount,
            Trip::COLUMN_ACCESSIBILITY_PRICE => $accessibilityCost > 0 ? $accessibilityCost : null,
            Trip::COLUMN_WAITING_PRICE => null,
            Trip::COLUMN_TOTAL_PRICE => $totalPrice,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Trip::COLUMN_STATUS => TripStatusEnum::PENDING,
        ]);

        // Bulk insert trip locations
        $now = now();
        $locations = [
            [
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_LOCATION_TITLE => $originLocation['location_title'],
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $originLocation['location_sub_title'],
                TripLocation::COLUMN_LATITUDE => $dto->originLatitude,
                TripLocation::COLUMN_LONGITUDE => $dto->originLongitude,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
                TripLocation::COLUMN_SEQUENCE => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_LOCATION_TITLE => $destinationLocation['location_title'],
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $destinationLocation['location_sub_title'],
                TripLocation::COLUMN_LATITUDE => $dto->destinationLatitude,
                TripLocation::COLUMN_LONGITUDE => $dto->destinationLongitude,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
                TripLocation::COLUMN_SEQUENCE => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        TripLocation::insert($locations);

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

    public function updateTripPrices(Trip $trip, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip
    {
        $trip->update([
            Trip::COLUMN_ACCESSIBILITY_PRICE => $accessibilityPrice,
            Trip::COLUMN_WAITING_PRICE => $waitingPrice,
            Trip::COLUMN_TOTAL_PRICE => $totalPrice,
        ]);

        return $trip->fresh();
    }
}
