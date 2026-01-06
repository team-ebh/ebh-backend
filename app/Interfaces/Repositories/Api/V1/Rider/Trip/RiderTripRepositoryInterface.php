<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Rider\Trip;

use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use Illuminate\Database\Eloquent\Collection;

interface RiderTripRepositoryInterface
{
    public function getRider(int $riderId): Rider;

    public function getRiderWithVehicle(int $riderId): Rider;

    public function getTripRequests(int $riderId): Collection;

    public function getActiveTrip(int $riderId): ?Trip;

    public function acceptTripRequestWithLock(TripRequest $tripRequest, Rider $rider): Trip;

    public function declineTripRequest(TripRequest $tripRequest): void;

    public function cancelTripRequestWithLock(TripRequest $tripRequest): Trip;

    public function updateRiderStatusToOnline(int $riderId): void;

    public function updateRiderStatus(Rider $rider, RiderStatusEnum $status): void;

    public function updateTripLocationStatus(TripLocation $location, TripLocationStatusEnum $status): void;

    public function updateTripStatus(Trip $trip, TripStatusEnum $status): void;

    public function updateTripWaitingTimeAndPrice(Trip $trip, int $waitingTime, float $waitingPrice): void;

    /**
     * Calculate and update waiting time for ROUND_TRIP_WAIT trips
     * Returns true if waiting time was calculated and updated, false otherwise
     */
    public function calculateAndUpdateWaitingTime(Trip $trip): bool;

    public function updateTripCommission(Trip $trip, float $commissionRate, string | float $commissionAmount): void;

    public function existsActiveTrip(int $riderId): bool;
}
