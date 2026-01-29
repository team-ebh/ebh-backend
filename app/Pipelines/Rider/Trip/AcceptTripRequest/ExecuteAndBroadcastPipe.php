<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\AcceptTripRequest;

use App\Events\Socket\Customer\TripAcceptedEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Jobs\Trip\LockRemainingTripRequestsJob;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
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
        $rider = $payload['rider'];

        // Accept trip with lock and save vehicle snapshot (inside transaction)
        $trip = $this->riderTripRepository->acceptTripRequestWithLock($dto->tripRequest, $rider);
        $payload['trip'] = $trip;

        // Lock all other pending trip requests for this trip
        LockRemainingTripRequestsJob::dispatch(
            $trip->{Trip::COLUMN_ID},
            $dto->tripRequest->{TripRequest::COLUMN_ID}
        );

        // Broadcast to customer
        broadcast(new TripAcceptedEvent(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            tripId: $trip->{Trip::COLUMN_ID},
            riderId: $rider->{Rider::COLUMN_ID}
        ));

        return $next($payload);
    }
}
