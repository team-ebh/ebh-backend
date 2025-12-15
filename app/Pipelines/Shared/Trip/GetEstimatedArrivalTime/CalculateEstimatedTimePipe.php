<?php

declare(strict_types=1);

namespace App\Pipelines\Shared\Trip\GetEstimatedArrivalTime;

use App\Models\Rider;
use App\Models\TripLocation;
use App\Services\DistanceCalculationService;
use Closure;

/**
 * Calculate Estimated Time Pipe
 *
 * Shared pipeline for calculating estimated arrival time between rider location and destination.
 * Used by both Customer and Rider APIs.
 */
readonly class CalculateEstimatedTimePipe
{
    public function __construct(
        private DistanceCalculationService $distanceCalculationService,
    ) {}

    /**
     * Handle the pipeline
     *
     * Expects payload with:
     * - 'rider': Rider model with location
     * - 'currentLocation': TripLocation model with destination coordinates
     *
     * Returns payload with:
     * - 'result': Array containing 'estimated_arrival_seconds'
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
