<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Rider\Trip;

use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

class RiderTripRepository implements RiderTripRepositoryInterface
{
    public function __construct(
        private readonly TripRequestRepositoryInterface $tripRequestRepository
    ) {}

    /**
     * Get available trip requests for a rider
     */
    public function getTripRequests(int $riderId): Collection
    {
        // Get rider
        $rider = Rider::query()
            ->with('vehicle.carType')
            ->findOrFail($riderId);

        // Get trip requests for this rider
        $tripRequests = $this->tripRequestRepository->getPendingForRider($rider);

        // Extract trips from trip requests
        return $tripRequests->map(fn ($tripRequest) => $tripRequest->trip);
    }

    /**
     * Accept trip request with lock
     */
    public function acceptTripWithLock(Trip $trip, Rider $rider): Trip
    {
        // Lock the trip for update
        $trip = Trip::query()
            ->where(Trip::COLUMN_ID, $trip->id)
            ->lockForUpdate()
            ->first();

        // Update trip with rider assignment
        $trip->update([
            'rider_id' => $rider->id,
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
        ]);

        // Reload with relationships
        $trip->load(['customer', 'locations', 'accessibility']);

        return $trip;
    }

    /**
     * Log trip decline
     */
    public function logTripDecline(Trip $trip, Rider $rider, string $reason): void
    {
        // TODO: Create a rider_trip_declines table to log declines
        // This will be useful for:
        // 1. Analytics - understand why trips are being declined
        // 2. Finding next available rider
        // 3. Preventing the same rider from seeing the same trip again
        //
        // For now, we just mark the trip as declined
        // Note: This might need refinement based on business logic
        // (e.g., should we only mark as DECLINED after all riders decline it?)
        $trip->update([
            Trip::COLUMN_STATUS => TripStatusEnum::DECLINED,
        ]);
    }
}
