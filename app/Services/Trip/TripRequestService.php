<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Events\Socket\Rider\NewTripRequestEvent;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Trip;

/**
 * Trip Request Service
 *
 * Manages the lifecycle of trip requests sent to riders
 */
readonly class TripRequestService
{
    public function __construct(
        private TripRequestRepositoryInterface $tripRequestRepository,
        private RiderMatchingService $riderMatchingService,
        private TripDataFormatterService $tripDataFormatter,
    ) {}

    /**
     * Send trip requests to eligible riders
     *
     * @param  Trip  $trip  The trip to send requests for
     * @param  int  $searchAttempt  Which search attempt (1-based)
     * @return int Number of riders who received the request
     */
    public function sendRequestsToRiders(Trip $trip, int $searchAttempt = 1): int
    {
        // Get search radius for this attempt
        $radiusMeters = $this->riderMatchingService->getSearchRadiusForAttempt($searchAttempt);

        // Find eligible riders
        $eligibleRiders = $this->riderMatchingService->findEligibleRiders($trip, $radiusMeters);

        if ($eligibleRiders->isEmpty()) {
            return 0;
        }

        // Create trip request records
        $tripRequests = $this->tripRequestRepository->createForRiders(
            $trip,
            $eligibleRiders,
            $searchAttempt,
            $radiusMeters
        );

        // Ensure trip has necessary relations loaded
        $trip->loadMissing(['locations', 'accessibility']);

        // Broadcast to each rider with properly formatted trip data
        foreach ($tripRequests as $tripRequest) {
            $tripData = $this->tripDataFormatter->prepareTripData($tripRequest);

            broadcast(new NewTripRequestEvent(
                riderId: $tripRequest->rider_id,
                tripData: $tripData
            ));
        }

        return $eligibleRiders->count();
    }

    /**
     * Check if we should retry sending requests with larger radius
     *
     * @param  Trip  $trip  The trip
     * @param  int  $currentAttempt  Current search attempt
     * @return bool True if should retry
     */
    public function shouldRetryWithLargerRadius(Trip $trip, int $currentAttempt): bool
    {
        // Check if auto retry is enabled
        if (! config('trip.request.auto_retry_on_all_declined', true)) {
            return false;
        }

        // Check if we haven't exceeded max attempts
        if ($currentAttempt >= $this->riderMatchingService->getMaxSearchAttempts()) {
            return false;
        }

        // Check if there are any pending requests left
        $pendingCount = $this->tripRequestRepository->getPendingCountForTrip($trip);

        return $pendingCount === 0;
    }

    /**
     * Retry sending requests with next radius
     *
     * @param  Trip  $trip  The trip
     * @param  int  $nextAttempt  Next search attempt number
     * @return int Number of new riders who received the request
     */
    public function retryWithLargerRadius(Trip $trip, int $nextAttempt): int
    {
        return $this->sendRequestsToRiders($trip, $nextAttempt);
    }
}
