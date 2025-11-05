<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use Closure;

/**
 * Calculate Distance Pipe
 *
 * Calculates distance between origin and destination using Haversine formula
 */
class CalculateDistancePipe
{
    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Only calculate distance if we have location data
        if ($context->dto->originLatitude !== null &&
            $context->dto->originLongitude !== null &&
            $context->dto->destinationLatitude !== null &&
            $context->dto->destinationLongitude !== null) {
            $context->distance = $this->calculateDistance(
                $context->dto->originLatitude,
                $context->dto->originLongitude,
                $context->dto->destinationLatitude,
                $context->dto->destinationLongitude
            );
        }

        return $next($context);
    }

    /**
     * Calculate distance using Haversine formula
     */
    private function calculateDistance(
        float $originLat,
        float $originLon,
        float $destLat,
        float $destLon
    ): float {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($originLat);
        $lonFrom = deg2rad($originLon);
        $latTo = deg2rad($destLat);
        $lonTo = deg2rad($destLon);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
