<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CancelTrip;

use App\Events\Socket\Customer\TripCancelledByRiderEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use Closure;

readonly class ExecuteAndBroadcastPipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];

        // Cancel trip with lock (inside transaction)
        $trip = $this->riderTripRepository->cancelTripRequestWithLock($dto->tripRequest);
        $payload['trip'] = $trip;

        // Update rider status to ONLINE
        $this->riderTripRepository->updateRiderStatusToOnline($dto->riderId);

        // Notify customer about trip cancellation
        broadcast(new TripCancelledByRiderEvent(
            customerId: $trip->customer_id,
            tripId: $trip->id,
            riderId: $dto->riderId
        ));

        return $next($payload);
    }
}
