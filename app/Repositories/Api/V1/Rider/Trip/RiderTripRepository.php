<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Rider\Trip;

use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripHistoryFilterEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Services\Trip\TripSnapshotService;
use App\Services\TripPricingService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

readonly class RiderTripRepository implements RiderTripRepositoryInterface
{
    public function __construct(
        private TripRequestRepositoryInterface $tripRequestRepository,
        private TripPricingService $tripPricingService,
        private TripSnapshotService $tripSnapshotService,
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
            ->activeTrips()
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

        // Create vehicle snapshot
        $vehicleSnapshot = $this->tripSnapshotService->createVehicleSnapshot($rider);

        // Update trip with rider assignment, status, and vehicle snapshot
        $trip->update([
            Trip::COLUMN_RIDER_ID => $rider->{Rider::COLUMN_ID},
            Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
            Trip::COLUMN_VEHICLE_SNAPSHOT => $vehicleSnapshot,
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

    /**
     * Update rider status to ONLINE
     */
    public function updateRiderStatusToOnline(int $riderId): void
    {
        $rider = Rider::query()->findOrFail($riderId);
        $rider->update([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
    }

    /**
     * Update rider status
     */
    public function updateRiderStatus(Rider $rider, RiderStatusEnum $status): void
    {
        $rider->update([
            Rider::COLUMN_STATUS => $status,
        ]);
    }

    /**
     * Update trip location status
     */
    public function updateTripLocationStatus(TripLocation $location, TripLocationStatusEnum $status): void
    {
        $location->update([
            TripLocation::COLUMN_STATUS => $status,
        ]);
    }

    /**
     * Update trip status
     */
    public function updateTripStatus(Trip $trip, TripStatusEnum $status): void
    {
        $trip->update([
            Trip::COLUMN_STATUS => $status,
        ]);
    }

    /**
     * Update trip waiting time, price, and config
     *
     * @param  Trip  $trip  The trip to update
     * @param  int  $waitingTime  The waiting time in minutes
     * @param  float|null  $waitingPrice  The waiting price (null if below minimum interval)
     * @param  array  $waitingTimeConfig  The config used for calculation (rate, interval)
     */
    public function updateTripWaitingTimeAndPrice(Trip $trip, int $waitingTime, ?float $waitingPrice, array $waitingTimeConfig): void
    {
        $currentTotalPrice = $trip->{Trip::COLUMN_TOTAL_PRICE} ?? 0.0;

        $trip->update([
            Trip::COLUMN_WAITING_TIME => $waitingTime,
            Trip::COLUMN_WAITING_PRICE => $waitingPrice,
            Trip::COLUMN_WAITING_TIME_CONFIG => $waitingTimeConfig,
            Trip::COLUMN_TOTAL_PRICE => $waitingPrice !== null ? $currentTotalPrice + $waitingPrice : $currentTotalPrice,
        ]);
    }

    /**
     * Calculate and update waiting time for ROUND_TRIP_WAIT trips
     * Returns true if waiting time was calculated and updated, false otherwise
     *
     * Note: Waiting time is ALWAYS persisted if calculated (even if below minimum interval).
     * Waiting price is only set when waiting time exceeds the minimum interval.
     */
    public function calculateAndUpdateWaitingTime(Trip $trip): bool
    {
        // Skip if waiting time is already calculated
        if ($trip->{Trip::COLUMN_WAITING_TIME} !== null && $trip->{Trip::COLUMN_WAITING_TIME} > 0) {
            return false;
        }

        // Load locations with status logs
        $trip->loadMissing(['locations.statusLogs']);

        // Calculate actual waiting time from status logs
        $waitingTimeMinutes = $this->tripPricingService->calculateActualWaitingTime($trip->locations);

        if ($waitingTimeMinutes === null || $waitingTimeMinutes < 0) {
            return false;
        }

        // Get the config values used for calculation
        $waitingTimeConfig = $this->tripPricingService->getWaitingTimeConfigRaw();

        // Calculate waiting charge (will be null if below minimum interval)
        $waitingCharge = $this->tripPricingService->calculateWaitingCharge($waitingTimeMinutes);

        // Update trip with waiting time, price (may be null), and config
        $this->updateTripWaitingTimeAndPrice($trip, $waitingTimeMinutes, $waitingCharge, $waitingTimeConfig);

        return true;
    }

    /**
     * Check if rider has an active trip
     */
    public function existsActiveTrip(int $riderId): bool
    {
        return Trip::query()
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->activeTrips()
            ->exists();
    }

    /**
     * Get past trips for a rider (completed and canceled)
     */
    public function getPastTrips(int $riderId, TripHistoryFilterEnum $filter): CursorPaginator
    {
        $query = Trip::query()
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->with([
                'locations:id,trip_id,location_title,location_sub_title,type,sequence',
            ])
            ->orderByDesc(Trip::COLUMN_ID);

        // Apply filter
        match ($filter) {
            TripHistoryFilterEnum::COMPLETED => $query->completed(),
            TripHistoryFilterEnum::CANCELED => $query->canceled(),
            TripHistoryFilterEnum::ALL => $query->pastTrips(),
        };

        return $query->cursorPaginate(5);
    }

    /**
     * Get past trip with details for a rider
     */
    public function getPastTripWithDetails(int $tripId, int $riderId): ?Trip
    {
        return Trip::query()
            ->where(Trip::COLUMN_ID, $tripId)
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->pastTrips()
            ->with([
                'locations:id,trip_id,location_title,location_sub_title,type,sequence',
                'locations.statusLogs:id,trip_location_id,status,created_at',
                'accessibility',
                'order:id,payment_method,status,total_price,currency',
            ])
            ->first();
    }

    /**
     * Get total completed rides count for a rider
     */
    public function getTotalRidesCount(int $riderId): int
    {
        return Trip::query()
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED)
            ->count();
    }

    /**
     * Get canceled trips count for a rider
     */
    public function getCanceledCount(int $riderId): int
    {
        return Trip::query()
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->whereIn(Trip::COLUMN_STATUS, [
                TripStatusEnum::CANCELED_BY_CUSTOMER,
                TripStatusEnum::CANCELLED_BY_RIDER,
            ])
            ->count();
    }

    /**
     * Save picked up timestamp (first pickup only)
     */
    public function savePickedUpAt(Trip $trip): void
    {
        // Only save if not already set (first pickup)
        if ($trip->{Trip::COLUMN_PICKED_UP_AT} === null) {
            $trip->update([
                Trip::COLUMN_PICKED_UP_AT => now(),
            ]);
        }
    }

    /**
     * Finalize trip completion with all data in a single update
     *
     * Updates: status, commission, completed_at, duration, distance
     */
    public function finalizeTripCompletion(
        Trip $trip,
        float $commissionRate,
        string $commissionAmount,
        int $durationMinutes,
        int $distanceMeters
    ): void {
        $trip->update([
            Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
            Trip::COLUMN_COMMISSION_RATE => $commissionRate,
            Trip::COLUMN_COMMISSION_AMOUNT => $commissionAmount,
            Trip::COLUMN_COMPLETED_AT => now(),
            Trip::COLUMN_DURATION_MINUTES => $durationMinutes,
            Trip::COLUMN_DISTANCE_METERS => $distanceMeters,
        ]);
    }
}
