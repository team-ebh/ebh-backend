<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\ArriveTrip;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Events\Socket\Customer\TripArrivedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripActionService;
use Closure;

class ExecuteAndBroadcastPipe
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
        $dto = $payload['dto'];
        $trip = $payload['trip'];
        $currentLocation = $payload['currentLocation'];

        // Update location status
        $this->riderTripRepository->updateTripLocationStatus($currentLocation, TripLocationStatusEnum::ARRIVED);

        // Broadcast to customer
        broadcast(new TripArrivedEvent(
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
