<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Database\Eloquent\Collection;

interface TripRequestRepositoryInterface
{
    /**
     * Create trip requests for multiple riders
     *
     * @return Collection<int, TripRequest>
     */
    public function createForRiders(
        Trip $trip,
        Collection $riders,
        int $searchAttempt,
        int $searchRadiusMeters
    ): Collection;

    /**
     * Get pending trip requests for a specific trip
     */
    public function getPendingForTrip(Trip $trip): Collection;

    /**
     * Get pending trip requests for a specific rider
     */
    public function getPendingForRider(int $riderId): Collection;

    /**
     * Get accepted trip request for a specific trip
     */
    public function getAcceptedForTrip(int $tripId, int $riderId): ?TripRequest;

    /**
     * Get a specific trip request by trip and rider
     */
    public function getByTripAndRider(Trip $trip, Rider $rider): ?TripRequest;

    /**
     * Cancel all pending trip requests for a trip
     */
    public function cancelPendingByTrip(Trip $trip, string $reason = 'trip_assigned'): int;

    /**
     * Cancel all pending trip requests for a rider
     */
    public function cancelPendingByRider(Rider $rider, string $reason = 'rider_busy'): int;

    /**
     * Mark expired trip requests as expired
     */
    public function markExpiredRequests(): int;

    /**
     * Get count of pending requests for a trip
     */
    public function getPendingCountForTrip(Trip $trip): int;

    /**
     * Check if rider has pending request for trip
     */
    public function hasPendingRequest(Trip $trip, Rider $rider): bool;
}
