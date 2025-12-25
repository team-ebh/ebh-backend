<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Enums\Trip\RideTypeEnum;
use App\Services\TripPricingService;
use Closure;

/**
 * Calculate Distance Pipe
 *
 * Calculates total distance for the trip based on ride type
 */
readonly class CalculateDistancePipe
{
    public function __construct(
        private TripPricingService $pricingService
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        $context->returnDistance = 0;

        // Calculate current distance (origin -> current last destination) (A→B)
        $context->distance = $this->pricingService->calculateDistance(
            $context->dto->firstOriginLatitude,
            $context->dto->firstOriginLongitude,
            $context->dto->lastDestinationLatitude,
            $context->dto->lastDestinationLongitude
        );

        // If ride type has second destination, calculate distance to new destination
        if (RideTypeEnum::hasSecondDestination($context->dto->rideTypeId)) {
            // returnDistance = distance from first destination to second destination (B→C)
            $context->returnDistance = $this->pricingService->calculateDistance(
                $context->dto->lastDestinationLatitude,
                $context->dto->lastDestinationLongitude,
                $context->dto->destinationLatitude,
                $context->dto->destinationLongitude
            );
        }

        return $next($context);
    }
}
