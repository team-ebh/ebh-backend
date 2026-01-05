<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Order;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, ?float $baseFare, ?float $roundTripPrice, ?float $accessibilityCost, float $totalPrice): Trip
    {
        // Create trip
        $trip = Trip::query()->create([
            Trip::COLUMN_CUSTOMER_ID => $dto->customerId,
            Trip::COLUMN_TRIP_TYPE_ID => $dto->tripTypeId,
            Trip::COLUMN_VEHICLE_TYPE_ID => $dto->vehicleTypeId,
            Trip::COLUMN_PASSENGER_COUNT => $dto->passengerCount,
            Trip::COLUMN_BASE_FARE => $baseFare,
            Trip::COLUMN_ROUND_TRIP_PRICE => $roundTripPrice,
            Trip::COLUMN_ACCESSIBILITY_PRICE => ($accessibilityCost && $accessibilityCost > 0) ? $accessibilityCost : null,
            Trip::COLUMN_WAITING_PRICE => null,
            Trip::COLUMN_TOTAL_PRICE => $totalPrice,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
        ]);

        $locations = [
            [
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_LOCATION_TITLE => $originLocation['location_title'],
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $originLocation['location_sub_title'],
                TripLocation::COLUMN_LATITUDE => $dto->originLatitude,
                TripLocation::COLUMN_LONGITUDE => $dto->originLongitude,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_SEQUENCE => 1,
            ],
            [
                TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
                TripLocation::COLUMN_LOCATION_TITLE => $destinationLocation['location_title'],
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $destinationLocation['location_sub_title'],
                TripLocation::COLUMN_LATITUDE => $dto->destinationLatitude,
                TripLocation::COLUMN_LONGITUDE => $dto->destinationLongitude,
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_SEQUENCE => 2,
            ],
        ];

        foreach ($locations as $location) {
            TripLocation::query()->create($location);
        }

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

        TripAccessibility::query()->insert($data);

        $trip->refresh();
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
            ->where(function ($query) {
                // Exclude draft scheduled trips (demand trips)
                $query->where(function ($q) {
                    $q->where(Trip::COLUMN_STATUS, '!=', TripStatusEnum::DRAFT)
                        ->orWhere(Trip::COLUMN_TRIP_TYPE_ID, '!=', TripTypeEnum::SCHEDULED->value);
                });
            })
            ->with(['customer', 'locations', 'accessibility'])
            ->orderByDesc(Trip::COLUMN_ID)
            ->get();
    }

    public function getActiveTrip(int $customerId): ?Trip
    {
        return Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->activeTrips()
            ->orderByDesc(Trip::COLUMN_ID)
            ->first();
    }

    public function getLastTrip(int $customerId): ?Trip
    {
        return Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->excludingDraftAndCancelled()
            ->orderByDesc(Trip::COLUMN_ID)
            ->first();
    }

    public function updateStatus(Trip $trip, TripStatusEnum $status): Trip
    {
        $trip->update([
            Trip::COLUMN_STATUS => $status,
        ]);

        return $trip->fresh();
    }

    public function updateOrderAndStatus(Trip $trip, int $orderId, TripStatusEnum $status): Trip
    {
        // Update trip status and associate with order
        $trip->update([
            Trip::COLUMN_ORDER_ID => $orderId,
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

    public function updateDestinationLocation(Trip $trip, ?string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude): void
    {
        TripLocation::query()
            ->where(TripLocation::COLUMN_TRIP_ID, $trip->{Trip::COLUMN_ID})
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
            ->update([
                TripLocation::COLUMN_LOCATION_TITLE => $locationTitle,
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $locationSubTitle,
                TripLocation::COLUMN_LATITUDE => $latitude,
                TripLocation::COLUMN_LONGITUDE => $longitude,
            ]);
    }

    public function updateRideTypeAndPrices(Trip $trip, RideTypeEnum $rideType, float $baseFare, ?float $roundTripPrice, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip
    {
        $trip->update([
            Trip::COLUMN_RIDE_TYPE => $rideType,
            Trip::COLUMN_BASE_FARE => $baseFare,
            Trip::COLUMN_ROUND_TRIP_PRICE => $roundTripPrice,
            Trip::COLUMN_ACCESSIBILITY_PRICE => $accessibilityPrice,
            Trip::COLUMN_WAITING_PRICE => $waitingPrice,
            Trip::COLUMN_TOTAL_PRICE => $totalPrice,
        ]);

        return $trip->fresh();
    }

    public function createDestinationLocation(Trip $trip, string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude, int $sequence): TripLocation
    {
        return TripLocation::query()->create([
            TripLocation::COLUMN_TRIP_ID => $trip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_LOCATION_TITLE => $locationTitle,
            TripLocation::COLUMN_LOCATION_SUB_TITLE => $locationSubTitle,
            TripLocation::COLUMN_LATITUDE => $latitude,
            TripLocation::COLUMN_LONGITUDE => $longitude,
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => $sequence,
        ]);
    }

    public function deleteAdditionalDestinations(Trip $trip): void
    {
        // Delete all destination locations except the first one (sequence 2)
        TripLocation::query()
            ->where(TripLocation::COLUMN_TRIP_ID, $trip->{Trip::COLUMN_ID})
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
            ->where(TripLocation::COLUMN_SEQUENCE, '>', 2)
            ->delete();
    }

    public function createDemandTrip(Trip $sourceTrip, array $originLocation, array $destinationLocation, ?int $scheduledTime, ?float $baseFare = null, ?float $roundTripPrice = null, ?float $accessibilityCost = null, ?float $totalPrice = null): Trip
    {
        // Create demand trip with SCHEDULED type
        $demandTrip = Trip::query()->create([
            Trip::COLUMN_CUSTOMER_ID => $sourceTrip->{Trip::COLUMN_CUSTOMER_ID},
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::SCHEDULED->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY,
            Trip::COLUMN_VEHICLE_TYPE_ID => $sourceTrip->{Trip::COLUMN_VEHICLE_TYPE_ID},
            Trip::COLUMN_PASSENGER_COUNT => $sourceTrip->{Trip::COLUMN_PASSENGER_COUNT},
            Trip::COLUMN_BASE_FARE => $baseFare,
            Trip::COLUMN_ROUND_TRIP_PRICE => $roundTripPrice,
            Trip::COLUMN_ACCESSIBILITY_PRICE => $accessibilityCost,
            Trip::COLUMN_WAITING_PRICE => null,
            Trip::COLUMN_TOTAL_PRICE => $totalPrice,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
            Trip::COLUMN_SCHEDULED_TIME => $scheduledTime ? Carbon::createFromTimestamp($scheduledTime) : null,
            Trip::COLUMN_DEMAND_TRIP_ID => $sourceTrip->{Trip::COLUMN_ID},
        ]);

        // Create origin location (last destination of source trip)
        TripLocation::query()->create([
            TripLocation::COLUMN_TRIP_ID => $demandTrip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_LOCATION_TITLE => $originLocation['title'],
            TripLocation::COLUMN_LOCATION_SUB_TITLE => $originLocation['sub_title'],
            TripLocation::COLUMN_LATITUDE => $originLocation['latitude'],
            TripLocation::COLUMN_LONGITUDE => $originLocation['longitude'],
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 1,
        ]);

        // Create destination location
        TripLocation::query()->create([
            TripLocation::COLUMN_TRIP_ID => $demandTrip->{Trip::COLUMN_ID},
            TripLocation::COLUMN_LOCATION_TITLE => $destinationLocation['title'],
            TripLocation::COLUMN_LOCATION_SUB_TITLE => $destinationLocation['sub_title'],
            TripLocation::COLUMN_LATITUDE => $destinationLocation['latitude'],
            TripLocation::COLUMN_LONGITUDE => $destinationLocation['longitude'],
            TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION,
            TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
            TripLocation::COLUMN_SEQUENCE => 2,
        ]);

        // Copy accessibility requirements from source trip to demand trip
        $sourceAccessibility = $sourceTrip->accessibility;
        if ($sourceAccessibility->isNotEmpty()) {
            $accessibilityData = $sourceAccessibility->map(fn ($item) => [
                TripAccessibility::COLUMN_TRIP_ID => $demandTrip->{Trip::COLUMN_ID},
                TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT => $item->{TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT},
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            TripAccessibility::query()->insert($accessibilityData);
        }

        return $demandTrip->fresh(['locations', 'accessibility']);
    }

    public function deleteDraftTrips(int $customerId): void
    {
        Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::DRAFT)
            ->delete();
    }
}
