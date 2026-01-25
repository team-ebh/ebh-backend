<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface
{
    public function createTrip(TripStoreDTO $dto, array $originLocation, array $destinationLocation, ?float $baseFare, ?float $roundTripPrice, ?float $accessibilityCost, float $totalPrice): Trip;

    public function attachAccessibilityRequirements(Trip $trip, array $requirements): void;

    public function findById(int $id): ?Trip;

    public function getCustomerTrips(int $customerId): Collection;

    public function getActiveTrip(int $customerId): ?Trip;

    public function getLastTrip(int $customerId): ?Trip;

    public function updateStatus(Trip $trip, TripStatusEnum $status): Trip;

    public function updateOrderAndStatus(Trip $trip, int $orderId, TripStatusEnum $status): Trip;

    public function updateTripPrices(Trip $trip, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip;

    public function updateDestinationLocation(Trip $trip, ?string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude): void;

    public function updateRideTypeAndPrices(Trip $trip, RideTypeEnum $rideType, float $baseFare, ?float $roundTripPrice, ?float $accessibilityPrice, ?float $waitingPrice, float $totalPrice): Trip;

    public function createDestinationLocation(Trip $trip, string $locationTitle, ?string $locationSubTitle, float $latitude, float $longitude, int $sequence): TripLocation;

    public function createDemandTrip(Trip $sourceTrip, array $originLocation, array $destinationLocation, ?int $scheduledTime, ?float $baseFare = null, ?float $roundTripPrice = null, ?float $accessibilityCost = null, ?float $totalPrice = null): Trip;

    public function deleteDraftTrips(int $customerId): void;

    public function getUpcomingTrips(int $customerId): CursorPaginator;

    public function getPastTrips(int $customerId): CursorPaginator;

    public function getUpcomingTripWithDetails(int $tripId, int $customerId): ?Trip;

    public function getPastTripWithDetails(int $tripId, int $customerId): ?Trip;

    public function getTripWithDetails(int $tripId, int $customerId): ?Trip;
}
