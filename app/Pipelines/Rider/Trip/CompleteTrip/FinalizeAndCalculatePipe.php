<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripCompletedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripActionService;
use Closure;

class FinalizeAndCalculatePipe
{
    public function __construct(
        private readonly RiderTripRepositoryInterface $riderTripRepository,
        private readonly TripActionService $tripActionService,
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
            // Complete trip and update rider status
            $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::COMPLETED);

            // Broadcast to customer
            broadcast(new TripCompletedEvent(
                customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $trip->{Trip::COLUMN_ID},
                riderId: $trip->{Trip::COLUMN_RIDER_ID}
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
}
