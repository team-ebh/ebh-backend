<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Events\Socket\Customer\TripCompletedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripActionService;
use App\Services\Trip\TripSnapshotService;
use App\Services\TripPricingService;
use Closure;

readonly class FinalizeAndCalculatePipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripActionService $tripActionService,
        private TripSnapshotService $tripSnapshotService,
        private TripPricingService $tripPricingService,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $trip = $payload['trip'];

        // Check if all locations are finished
        $allLocationsFinished = $trip->loadMissing('locations')->locations->every(fn ($location) => $location->isFinished());

        if ($allLocationsFinished) {
            // Calculate and update waiting time for ROUND_TRIP_WAIT (fallback if not calculated during pickup)
            if ($trip->isRoundTripWithWait()) {
                $this->riderTripRepository->calculateAndUpdateWaitingTime($trip);
            }

            // Calculate commission data
            $commissionData = $this->tripPricingService->calculateCommission($trip);

            // Calculate duration and distance
            $tripMetrics = $this->tripSnapshotService->calculateDurationAndDistance($trip);

            // Finalize trip completion with all data in a single update
            $this->riderTripRepository->finalizeTripCompletion(
                trip: $trip,
                commissionRate: $commissionData['rate'],
                commissionAmount: $commissionData['amount'],
                durationMinutes: $tripMetrics['duration_minutes'],
                distanceMeters: $tripMetrics['distance_meters'],
            );

            // Broadcast to customer
            broadcast(new TripCompletedEvent(
                customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $trip->{Trip::COLUMN_ID},
                riderId: $trip->{Trip::COLUMN_RIDER_ID},
                hasPendingPayment: ! $trip->isRoundTrip() && $trip->isKnetPayment()
            ));

            $this->riderTripRepository->updateRiderStatusToOnline($trip->{Trip::COLUMN_RIDER_ID});

            $payload['trip_completed'] = true;
            $payload['next_action'] = null;
        } else {
            $payload['trip_completed'] = false;
            $payload['next_action'] = $this->tripActionService->getNextAction($trip->fresh())?->value;
        }

        return $next($payload);
    }
}
