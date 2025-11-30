<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\CompleteTrip;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use Closure;

class UpdateStatusPipe
{
    public function __construct(
        private readonly RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $currentLocation = $payload['currentLocation'];
        $isLastLocation = $payload['isLastLocation'];

        // Determine status
        $status = $isLastLocation
            ? TripLocationStatusEnum::COMPLETED
            : TripLocationStatusEnum::DROPPED_OFF;

        // Update location status in database
        $this->riderTripRepository->updateTripLocationStatus($currentLocation, $status);

        // Store status for next pipes
        $payload['locationStatus'] = $status;

        return $next($payload);
    }
}
