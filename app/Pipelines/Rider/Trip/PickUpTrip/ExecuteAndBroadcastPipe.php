<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\PickUpTrip;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripPickedUpEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
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

        // Update location status
        $this->riderTripRepository->updateTripLocationStatus($currentLocation, TripLocationStatusEnum::PICKED_UP);

        // Update trip status to IN_PROGRESS
        if ($trip->isArrived()) {
            $this->riderTripRepository->updateTripStatus($trip, TripStatusEnum::IN_PROGRESS);
        }

        // Broadcast to customer
        broadcast(new TripPickedUpEvent(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            tripId: $trip->{Trip::COLUMN_ID},
            riderId: $dto->riderId
        ));

        // Calculate next action
        $nextAction = $this->tripActionService->getNextAction($trip->fresh());
        $payload['next_action'] = $nextAction;

        return $next($payload);
    }
}
