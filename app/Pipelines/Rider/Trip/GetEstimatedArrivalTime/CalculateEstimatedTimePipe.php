<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\GetEstimatedArrivalTime;

use App\Models\Rider;
use App\Models\TripLocation;
use App\Services\DistanceCalculationService;
use Closure;

readonly class CalculateEstimatedTimePipe
{
    public function __construct(
        private DistanceCalculationService $distanceCalculationService,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $rider = $payload['rider'];
        $currentLocation = $payload['currentLocation'];

        $result = $this->distanceCalculationService->calculateDistanceAndDuration(
            originLatitude: (float) $rider->{Rider::COLUMN_LATITUDE},
            originLongitude: (float) $rider->{Rider::COLUMN_LONGITUDE},
            destinationLatitude: (float) $currentLocation->{TripLocation::COLUMN_LATITUDE},
            destinationLongitude: (float) $currentLocation->{TripLocation::COLUMN_LONGITUDE}
        );

        $payload['result'] = [
            'estimated_arrival_seconds' => $result['estimated_arrival_seconds'] ?? 0,
        ];

        return $next($payload);
    }
}
