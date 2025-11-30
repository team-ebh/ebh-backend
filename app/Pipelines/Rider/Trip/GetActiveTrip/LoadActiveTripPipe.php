<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\GetActiveTrip;

use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Interfaces\Repositories\TripRequestRepositoryInterface;
use App\Models\Trip;
use Closure;

class LoadActiveTripPipe
{
    public function __construct(
        private readonly RiderTripRepositoryInterface $riderTripRepository,
        private readonly TripRequestRepositoryInterface $tripRequestRepository,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];

        // Get active trip for rider
        $activeTrip = $this->riderTripRepository->getActiveTrip($dto->riderId);

        if (! $activeTrip) {
            $payload['result'] = null;

            return $payload;
        }

        // Get accepted trip request for this trip
        $tripRequest = $this->tripRequestRepository->getAcceptedForTrip(
            $activeTrip->{Trip::COLUMN_ID},
            $dto->riderId
        );

        if (! $tripRequest) {
            $payload['result'] = null;

            return $payload;
        }

        $payload['activeTrip'] = $activeTrip;
        $payload['tripRequest'] = $tripRequest;

        return $next($payload);
    }
}
