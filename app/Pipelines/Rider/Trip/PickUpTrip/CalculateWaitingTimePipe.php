<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\PickUpTrip;

use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use Closure;

/**
 * Calculate waiting time for ROUND_TRIP_WAIT when passenger is picked up after waiting.
 * This runs during the second pickup (after waiting at destination).
 */
readonly class CalculateWaitingTimePipe
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

        // Only calculate waiting time for ROUND_TRIP_WAIT during second pickup.
        // Second pickup is when: current location is a DESTINATION that is DROPPED_OFF
        if (! $trip->isRoundTripWithWait()) {
            return $next($payload);
        }

        if (! $currentLocation->isDestination() || ! $currentLocation->isDroppedOff()) {
            return $next($payload);
        }

        // Calculate and update waiting time
        $this->riderTripRepository->calculateAndUpdateWaitingTime($trip);

        return $next($payload);
    }
}
