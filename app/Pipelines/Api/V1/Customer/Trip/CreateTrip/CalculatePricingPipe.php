<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Enums\Trip\RideTypeEnum;
use App\Services\TripPricingService;
use Closure;

/**
 * Calculate Pricing Pipe
 *
 * Calculates trip pricing using TripPricingService
 * Supports dynamic pricing based on vehicle per-km rate, distance, and add-ons
 */
class CalculatePricingPipe
{
    public function __construct(
        private readonly TripPricingService $pricingService
    ) {}

    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        // Calculate distance
        $distance = $this->pricingService->calculateDistance(
            $context->dto->originLatitude,
            $context->dto->originLongitude,
            $context->dto->destinationLatitude,
            $context->dto->destinationLongitude
        );

        // For trip creation, we assume ONE_WAY as default ride type
        // The ride type can be changed later via ChangeRideTypeAction
        $pricing = $this->pricingService->calculatePriceByRideType(
            RideTypeEnum::ONE_WAY,
            $distance,
            $context->dto->accessibilityRequirements ?? [],
            null,
            null // TODO: Pass vehicle ID when available
        );

        // Store pricing information in context
        $context->baseFare = $pricing['base_fare'];
        $context->roundTripFee = $pricing['round_trip_fee'];
        $context->accessibilityCost = $pricing['accessibility_cost'];
        $context->estimatedPrice = $pricing['total_price'];

        return $next($context);
    }
}
