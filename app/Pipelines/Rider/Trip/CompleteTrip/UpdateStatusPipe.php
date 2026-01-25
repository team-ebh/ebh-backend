<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Events\Socket\Customer\TripNextDropOffEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use Closure;

readonly class UpdateStatusPipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $trip = $payload['trip'];
        $currentLocation = $payload['currentLocation'];
        $isLastLocation = $payload['isLastLocation'];

        // Determine status
        $status = $isLastLocation
            ? TripLocationStatusEnum::COMPLETED
            : TripLocationStatusEnum::DROPPED_OFF;

        // Update location status in database
        $this->riderTripRepository->updateTripLocationStatus($currentLocation, $status);

        // Broadcast drop off event for intermediate destinations (ROUND_TRIP_WAIT)
        if ($status === TripLocationStatusEnum::DROPPED_OFF) {
            broadcast(new TripNextDropOffEvent(
                customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $trip->{Trip::COLUMN_ID},
                riderId: $trip->{Trip::COLUMN_RIDER_ID},
            ));
        }

        // Store status for next pipes
        $payload['locationStatus'] = $status;

        return $next($payload);
    }
}
