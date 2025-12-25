<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, ?float $accessibilityCost, float $totalPrice): Trip;

    public function attachAccessibilityRequirements(Trip $trip, array $requirements): void;

    public function findById(int $id): ?Trip;

    public function getCustomerTrips(int $customerId): Collection;

    public function getActiveTrip(int $customerId): ?Trip;

    public function getLastTrip(int $customerId): ?Trip;

    public function updateStatus(Trip $trip, TripStatusEnum $status): Trip;

    public function updatePaymentMethod(Trip $trip, PaymentMethodEnum $paymentMethod): Trip;

    public function updatePaymentMethodAndStatus(Trip $trip, PaymentMethodEnum $paymentMethod, TripStatusEnum $status): Trip;

    public function updateTripPrices(Trip $trip, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip;

    public function updateDestinationLocation(Trip $trip, ?string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude): void;

    public function updateRideTypeAndPrices(Trip $trip, RideTypeEnum $rideType, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip;

    public function createDestinationLocation(Trip $trip, string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude, int $sequence): TripLocation;

    public function deleteAdditionalDestinations(Trip $trip): void;

    public function createDemandTrip(Trip $sourceTrip, array $originLocation, array $destinationLocation, ?int $scheduledTime): Trip;

    public function deleteDraftTrips(int $customerId): void;
}
