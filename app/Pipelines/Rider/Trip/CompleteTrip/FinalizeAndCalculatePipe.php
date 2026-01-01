<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripCompletedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripActionService;
use App\Services\TripPricingService;
use Closure;

readonly class FinalizeAndCalculatePipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripActionService $tripActionService,
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
            // Calculate and update waiting time for ROUND_TRIP_WAIT
            if ($trip->isRoundTripWithWait()) {
                $this->calculateAndUpdateWaitingTime($trip);
            }

            // Complete trip and update rider status
            $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::COMPLETED);

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
            $payload['next_action'] = $this->tripActionService->getNextAction($trip->fresh());
        }

        return $next($payload);
    }

    /**
     * Calculate and update waiting time for ROUND_TRIP_WAIT trips
     */
    private function calculateAndUpdateWaitingTime(Trip $trip): void
    {
        // Load locations with status logs
        $trip->loadMissing(['locations.statusLogs']);

        // Calculate actual waiting time from status logs
        $waitingTimeMinutes = $this->tripPricingService->calculateActualWaitingTime($trip->locations);

        if ($waitingTimeMinutes === null || $waitingTimeMinutes <= 0) {
            return;
        }

        // Calculate waiting charge
        $waitingCharge = $this->tripPricingService->calculateWaitingCharge($waitingTimeMinutes);

        if ($waitingCharge === null || $waitingCharge <= 0) {
            return;
        }

        // Update trip with waiting time and price
        $this->riderTripRepository->updateTripWaitingTimeAndPrice(
            $trip,
            $waitingTimeMinutes,
            $waitingCharge
        );
    }
}
