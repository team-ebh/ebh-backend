<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Rider\Trip;

use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Database\Eloquent\Collection;

readonly class RiderTripRepository implements RiderTripRepositoryInterface
{
    public function __construct(
        private TripRequestRepositoryInterface $tripRequestRepository
    ) {}

    /**
     * Get rider with vehicle relationships
     */
    public function getRiderWithVehicle(int $riderId): Rider
    {
        return Rider::query()
            ->with(['vehicle.carMake', 'vehicle.carModel', 'vehicle.carColor'])
            ->findOrFail($riderId);
    }

    /**
     * Get rider
     */
    public function getRider(int $riderId): Rider
    {
        return Rider::query()->findOrFail($riderId);
    }

    /**
     * Get available trip requests for a rider
     */
    public function getTripRequests(int $riderId): Collection
    {
        return $this->tripRequestRepository->getPendingForRider($riderId);
    }

    /**
     * Get rider's active trip (accepted but not completed/cancelled)
     */
    public function getActiveTrip(int $riderId): ?Trip
    {
        return Trip::query()
            ->forRider($riderId)
            ->whereIn(Trip::COLUMN_STATUS, [
                TripStatusEnum::ACCEPTED_RIDER,
                TripStatusEnum::ARRIVED,
                TripStatusEnum::PICKED_UP,
            ])
            ->with(['customer', 'locations', 'accessibility'])
            ->first();
    }

    /**
     * Accept trip request with lock
     */
    public function acceptTripRequestWithLock(TripRequest $tripRequest, Rider $rider): Trip
    {
        // Lock the trip for update
        $trip = Trip::query()
            ->where(Trip::COLUMN_ID, $tripRequest->{TripRequest::COLUMN_TRIP_ID})
            ->lockForUpdate()
            ->first();

        // Update trip request status to ACCEPTED
        $tripRequest->update([
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED,
            TripRequest::COLUMN_RESPONDED_AT => now(),
        ]);

        // Update trip with rider assignment and status
        $trip->update([
            Trip::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
        ]);

        // Update rider status to BUSY
        $rider->update([
            Rider::COLUMN_STATUS => RiderStatusEnum::BUSY,
        ]);

        // Reload with relationships
        $trip->load(['customer', 'locations', 'accessibility']);

        return $trip;
    }

    /**
     * Decline trip request
     */
    public function declineTripRequest(TripRequest $tripRequest): void
    {
        // Update trip request status to DECLINED
        $tripRequest->update([
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::DECLINED,
            TripRequest::COLUMN_RESPONDED_AT => now(),
        ]);
    }

    /**
     * Cancel trip request with lock
     */
    public function cancelTripRequestWithLock(TripRequest $tripRequest): Trip
    {
        // Lock the trip for update
        $trip = Trip::query()
            ->where(Trip::COLUMN_ID, $tripRequest->{TripRequest::COLUMN_TRIP_ID})
            ->lockForUpdate()
            ->first();

        // Update trip request status to CANCELLED
        $tripRequest->update([
            TripRequest::COLUMN_STATUS => TripRequestStatusEnum::CANCELLED,
            TripRequest::COLUMN_RESPONDED_AT => now(),
        ]);

        // Update trip status to cancelled by rider
        $trip->update([
            Trip::COLUMN_STATUS => TripStatusEnum::CANCELLED_BY_RIDER,
        ]);

        return $trip->fresh();
    }
}
