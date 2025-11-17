<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Events\Socket\Customer\TripSearchingForRiderEvent;
use App\Events\Socket\Rider\NewTripRequestEvent;
use App\Events\Socket\Rider\TripRequestCancelledEvent;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Rider;
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
        $this->tripRequestRepository->createForRiders(
            $trip,
            $eligibleRiders,
            $searchAttempt,
            $radiusMeters
        );

        // Broadcast to each rider
        foreach ($eligibleRiders as $rider) {
            broadcast(new NewTripRequestEvent(
                riderId: $rider->id,
                trip: $trip
            ));
        }

        // Broadcast to customer
        broadcast(new TripSearchingForRiderEvent(
            customerId: $trip->customer_id,
            tripId: $trip->id,
            riderCount: $eligibleRiders->count(),
            radiusMeters: $radiusMeters,
            searchAttempt: $searchAttempt
        ));

        return $eligibleRiders->count();
    }

    /**
     * Cancel all pending trip requests for a trip
     *
     * @param  Trip  $trip  The trip
     * @param  string  $reason  Reason for cancellation
     * @return int Number of requests cancelled
     */
    public function cancelPendingRequests(Trip $trip, string $reason = 'trip_assigned'): int
    {
        // Get pending requests before cancelling
        $pendingRequests = $this->tripRequestRepository->getPendingForTrip($trip);

        // Cancel them
        $cancelledCount = $this->tripRequestRepository->cancelPendingByTrip($trip, $reason);

        // Broadcast cancellation to each rider
        foreach ($pendingRequests as $tripRequest) {
            broadcast(new TripRequestCancelledEvent(
                riderId: $tripRequest->rider_id,
                tripId: $trip->id,
                reason: $reason
            ));
        }

        return $cancelledCount;
    }

    /**
     * Cancel all pending trip requests for a rider (e.g., when they become busy)
     *
     * @param  Rider  $rider  The rider
     * @param  string  $reason  Reason for cancellation
     * @return int Number of requests cancelled
     */
    public function cancelRiderPendingRequests(Rider $rider, string $reason = 'rider_busy'): int
    {
        return $this->tripRequestRepository->cancelPendingByRider($rider, $reason);
    }

    /**
     * Process expired trip requests
     *
     * This should be called by a scheduled job
     *
     * @return int Number of requests marked as expired
     */
    public function processExpiredRequests(): int
    {
        return $this->tripRequestRepository->markExpiredRequests();
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
