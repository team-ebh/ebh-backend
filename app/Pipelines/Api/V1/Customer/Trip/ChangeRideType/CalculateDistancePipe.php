<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Services\TripPricingService;
use Closure;

/**
 * Calculate Distance Pipe
 *
 * Calculates distance between origin and destination using TripPricingService
 */
class CalculateDistancePipe
{
    public function __construct(
        private readonly TripPricingService $pricingService
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Only calculate distance if we have location data
        if ($context->dto->originLatitude !== null &&
            $context->dto->originLongitude !== null &&
            $context->dto->destinationLatitude !== null &&
            $context->dto->destinationLongitude !== null) {
            $context->distance = $this->pricingService->calculateDistance(
                $context->dto->originLatitude,
                $context->dto->originLongitude,
                $context->dto->destinationLatitude,
                $context->dto->destinationLongitude
            );
        }

        return $next($context);
    }
}
