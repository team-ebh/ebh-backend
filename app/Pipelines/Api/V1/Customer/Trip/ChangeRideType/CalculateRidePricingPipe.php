<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Services\TripPricingService;
use Closure;

/**
 * Calculate Ride Pricing Pipe
 *
 * Calculates pricing based on ride type using TripPricingService
 * Supports dynamic pricing based on vehicle per-km rate, distance, and add-ons
 */
readonly class CalculateRidePricingPipe
{
    public function __construct(
        private TripPricingService $pricingService
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Skip pricing calculation if no distance (no location data)
        if ($context->distance === null) {
            return $next($context);
        }

        // Calculate pricing based on ride type using service
        $pricing = $this->pricingService->calculatePriceByRideType(
            $context->dto->rideTypeId,
            $context->distance,
            $context->dto->trip->accessibility,
            $context->dto->scheduledTime,
            null, // TODO: Pass vehicle ID when available
            $context->returnDistance
        );

        // Store pricing information in context
        $context->baseFare = $pricing['base_fare'];
        $context->accessibilityCost = $pricing['accessibility_cost'];
        $context->roundTripFee = $pricing['round_trip_fee'];
        $context->waitingCharge = $pricing['waiting_charge'];
        $context->totalPrice = $pricing['total_price'];

        return $next($context);
    }
}
