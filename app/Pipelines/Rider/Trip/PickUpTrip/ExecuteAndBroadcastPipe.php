<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\PickUpTrip;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripNextPickUpEvent;
use App\Events\Socket\Customer\TripPickedUpEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Services\Trip\TripActionService;
use Closure;

readonly class ExecuteAndBroadcastPipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripActionService $tripActionService,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];
        $trip = $payload['trip'];
        $currentLocation = $payload['currentLocation'];

        // For ROUND_TRIP_WAIT: if pickup at DROPPED_OFF destination, update NEXT destination to PICKED_UP
        // The current (DROPPED_OFF) destination stays as is
        $isNextPickup = $trip->isRoundTripWithWait() && $currentLocation->isDestination() && $currentLocation->isDroppedOff();

        if ($isNextPickup) {
            $nextDestination = $this->getNextDestination($trip, $currentLocation);
            if ($nextDestination) {
                $this->riderTripRepository->updateTripLocationStatus($nextDestination, TripLocationStatusEnum::PICKED_UP);

                // Broadcast next pickup event to customer
                broadcast(new TripNextPickUpEvent(
                    customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                    tripId: $trip->{Trip::COLUMN_ID},
                    riderId: $dto->riderId
                ));
            }
        } else {
            // Standard pickup at origin: mark current location as PICKED_UP
            $this->riderTripRepository->updateTripLocationStatus($currentLocation, TripLocationStatusEnum::PICKED_UP);

            // Broadcast standard pickup event to customer
            broadcast(new TripPickedUpEvent(
                customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $trip->{Trip::COLUMN_ID},
                riderId: $dto->riderId
            ));
        }

        // Update trip status to IN_PROGRESS
        if ($trip->isArrived()) {
            $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::IN_PROGRESS);
        }

        // Calculate next action
        $nextAction = $this->tripActionService->getNextAction($trip->fresh());
        $payload['next_action'] = $nextAction?->value;

        return $next($payload);
    }

    /**
     * Get the next destination after the current location
     */
    private function getNextDestination(Trip $trip, TripLocation $currentLocation): ?TripLocation
    {
        $locations = $trip->loadMissing('locations')->locations;
        $currentSequence = $currentLocation->{TripLocation::COLUMN_SEQUENCE};

        return $locations->first(function (TripLocation $loc) use ($currentSequence) {
            return $loc->isDestination()
                && $loc->{TripLocation::COLUMN_SEQUENCE} > $currentSequence
                && $loc->isPending();
        });
    }
}
