<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripAccessibility;
use App\Models\TripLocation;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, ?float $accessibilityCost, float $totalPrice): Trip
    {
        // Create trip
        $trip = Trip::query()->create([
            Trip::COLUMN_CUSTOMER_ID => $dto->customerId,
            Trip::COLUMN_TRIP_TYPE_ID => $dto->tripTypeId,
            Trip::COLUMN_VEHICLE_TYPE_ID => $dto->vehicleTypeId,
            Trip::COLUMN_PASSENGER_COUNT => $dto->passengerCount,
            Trip::COLUMN_ACCESSIBILITY_PRICE => ($accessibilityCost && $accessibilityCost > 0) ? $accessibilityCost : null,
            Trip::COLUMN_WAITING_PRICE => null,
            Trip::COLUMN_TOTAL_PRICE => $totalPrice,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD,
            Trip::COLUMN_STATUS => TripStatusEnum::DRAFT,
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
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
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
                TripLocation::COLUMN_STATUS => TripLocationStatusEnum::PENDING->value,
                TripLocation::COLUMN_SEQUENCE => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        TripLocation::query()->insert($locations);

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

    public function updatePaymentMethod(Trip $trip, PaymentMethodEnum $paymentMethod): Trip
    {
        $trip->update([
            Trip::COLUMN_PAYMENT_METHOD => $paymentMethod,
        ]);

        return $trip->fresh();
    }

    public function updatePaymentMethodAndStatus(Trip $trip, PaymentMethodEnum $paymentMethod, TripStatusEnum $status): Trip
    {
        $trip->update([
            Trip::COLUMN_PAYMENT_METHOD => $paymentMethod,
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

    public function deleteDraftTrips(int $customerId): void
    {
        Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::DRAFT)
            ->delete();
    }
}
