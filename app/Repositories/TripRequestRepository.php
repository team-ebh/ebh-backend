<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Trip\TripRequestStatusEnum;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripRequest;
use App\Models\TripRequestStatusLog;
use App\Services\DistanceCalculationService;
use Illuminate\Database\Eloquent\Collection;

readonly class TripRequestRepository implements TripRequestRepositoryInterface
{
    public function __construct(
        private DistanceCalculationService $distanceCalculationService
    ) {}

    /**
     * Create trip requests for multiple riders
     */
    public function createForRiders(
        Trip $trip,
        Collection $riders,
        int $searchAttempt,
        int $searchRadiusMeters
    ): void {
        $expirationSeconds = config('trip.request.expiration_seconds');
        $expiresAt = $expirationSeconds ? now()->addSeconds($expirationSeconds) : null;
        $now = now();

        // Get trip origin location
        $origin = $trip->locations()
            ->orderBy(TripLocation::COLUMN_SEQUENCE)
            ->first();

        $requests = $riders->map(function (Rider $rider, int $index) use ($trip, $searchAttempt, $searchRadiusMeters, $expiresAt, $origin, $now) {
            $distanceMeters = null;
            $estimatedArrivalSeconds = null;

            // Calculate distance and estimated arrival time if both trip origin and rider location are available
            if ($origin && isset($rider->latitude) && isset($rider->longitude)) {
                $calculation = $this->distanceCalculationService->calculateDistanceAndDuration(
                    $rider->latitude,
                    $rider->longitude,
                    $origin->{TripLocation::COLUMN_LATITUDE},
                    $origin->{TripLocation::COLUMN_LONGITUDE}
                );

                $distanceMeters = $calculation['distance_meters'];
                $estimatedArrivalSeconds = $calculation['estimated_arrival_seconds'];
            }

            return [
                TripRequest::COLUMN_TRIP_ID => $trip->id,
                TripRequest::COLUMN_RIDER_ID => $rider->id,
                TripRequest::COLUMN_DISTANCE_METERS => $distanceMeters,
                TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS => $estimatedArrivalSeconds,
                TripRequest::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
                TripRequest::COLUMN_SENT_AT => $now,
                TripRequest::COLUMN_SEARCH_RADIUS_METERS => $searchRadiusMeters,
                TripRequest::COLUMN_SEARCH_ATTEMPT => $searchAttempt,
                TripRequest::COLUMN_EXPIRES_AT => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        // Bulk insert trip requests (bypasses observers for performance)
        TripRequest::query()->insert($requests);

        // Manually create status logs for all inserted trip requests
        $this->createStatusLogsForBulkInsert($trip->id, $riders, $now);
    }

    /**
     * Create status logs for bulk inserted trip requests
     */
    private function createStatusLogsForBulkInsert(int $tripId, Collection $riders, \Carbon\CarbonInterface $timestamp): void
    {
        // Get the IDs of the newly created trip requests
        $tripRequestIds = TripRequest::query()
            ->where(TripRequest::COLUMN_TRIP_ID, $tripId)
            ->whereIn(TripRequest::COLUMN_RIDER_ID, $riders->pluck('id'))
            ->pluck(TripRequest::COLUMN_ID, TripRequest::COLUMN_RIDER_ID);

        // Prepare status logs for bulk insert
        $statusLogs = $tripRequestIds->map(function ($tripRequestId) use ($timestamp) {
            return [
                TripRequestStatusLog::COLUMN_TRIP_REQUEST_ID => $tripRequestId,
                TripRequestStatusLog::COLUMN_STATUS => TripRequestStatusEnum::PENDING->value,
                TripRequestStatusLog::COLUMN_CHANGED_BY_TYPE => null,
                TripRequestStatusLog::COLUMN_CHANGED_BY_ID => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->values()->toArray();

        // Bulk insert status logs
        TripRequestStatusLog::query()->insert($statusLogs);
    }

    /**
     * Get pending trip requests for a specific trip
     */
    public function getPendingForTrip(Trip $trip): Collection
    {
        return TripRequest::query()
            ->forTrip($trip->id)
            ->pending()
            ->notExpired()
            ->with('rider')
            ->orderBy(TripRequest::COLUMN_PRIORITY)
            ->get();
    }

    /**
     * Get pending trip requests for a specific rider
     */
    public function getPendingForRider(int $riderId): Collection
    {
        return TripRequest::query()
            ->forRider($riderId)
            ->pending()
//            ->notExpired()
            ->with(['trip.locations', 'trip.accessibility'])
            ->orderBy(TripRequest::COLUMN_SENT_AT, 'desc')
            ->get();
    }

    /**
     * Get accepted trip request for a specific trip
     */
    public function getAcceptedForTrip(int $tripId, int $riderId): ?TripRequest
    {
        return TripRequest::query()
            ->forTrip($tripId)
            ->forRider($riderId)
            ->accepted()
            ->with(['trip.locations', 'trip.accessibility'])
            ->first();
    }

    /**
     * Get a specific trip request by trip and rider
     */
    public function getByTripAndRider(Trip $trip, Rider $rider): ?TripRequest
    {
        return TripRequest::query()
            ->forTrip($trip->id)
            ->forRider($rider->id)
            ->pending()
            ->notExpired()
            ->first();
    }

    /**
     * Cancel all pending trip requests for a trip
     */
    public function cancelPendingByTrip(Trip $trip, string $reason = 'trip_assigned'): int
    {
        return TripRequest::query()
            ->forTrip($trip->id)
            ->pending()
            ->update([
                TripRequest::COLUMN_STATUS => TripRequestStatusEnum::CANCELLED->value,
                TripRequest::COLUMN_RESPONDED_AT => now(),
                TripRequest::COLUMN_DECLINE_REASON => $reason,
            ]);
    }

    /**
     * Cancel all pending trip requests for a rider
     */
    public function cancelPendingByRider(Rider $rider, string $reason = 'rider_busy'): int
    {
        return TripRequest::query()
            ->forRider($rider->id)
            ->pending()
            ->update([
                TripRequest::COLUMN_STATUS => TripRequestStatusEnum::CANCELLED->value,
                TripRequest::COLUMN_RESPONDED_AT => now(),
                TripRequest::COLUMN_DECLINE_REASON => $reason,
            ]);
    }

    /**
     * Mark expired trip requests as expired
     */
    public function markExpiredRequests(): int
    {
        return TripRequest::query()
            ->expired()
            ->update([
                TripRequest::COLUMN_STATUS => TripRequestStatusEnum::EXPIRED->value,
                TripRequest::COLUMN_RESPONDED_AT => now(),
            ]);
    }

    /**
     * Get count of pending requests for a trip
     */
    public function getPendingCountForTrip(Trip $trip): int
    {
        return TripRequest::query()
            ->forTrip($trip->id)
            ->pending()
            ->notExpired()
            ->count();
    }

    /**
     * Check if rider has pending request for trip
     */
    public function hasPendingRequest(Trip $trip, Rider $rider): bool
    {
        return TripRequest::query()
            ->forTrip($trip->id)
            ->forRider($rider->id)
            ->pending()
            ->notExpired()
            ->exists();
    }
}
