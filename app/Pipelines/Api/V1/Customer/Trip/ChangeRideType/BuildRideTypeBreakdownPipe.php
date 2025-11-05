<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Services\TripPricingService;
use Closure;

/**
 * Build Ride Type Breakdown Pipe
 *
 * Builds price breakdown, estimation, and waiting time config using TripPricingService
 */
class BuildRideTypeBreakdownPipe
{
    public function __construct(
        private readonly TripPricingService $pricingService
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Add waiting time config if needed
        if ($context->dto->rideTypeId->needsWaitingTimeConfig()) {
            $context->waitingTimeConfig = $this->pricingService->getWaitingTimeConfig();
        }

        // If no location data, return empty breakdown
        if ($context->distance === null) {
            $context->priceBreakdown = [];
            $context->priceEstimation = null;

            return $next($context);
        }

        // Build pricing array for breakdown
        $pricing = [
            'base_fare' => $context->baseFare,
            'accessibility_cost' => $context->accessibilityCost,
            'round_trip_fee' => $context->roundTripFee,
            'waiting_charge' => $context->waitingCharge,
            'total_price' => $context->totalPrice,
        ];

        // Build price breakdown using service
        $context->priceBreakdown = $this->pricingService->buildPriceBreakdown(
            $context->dto->rideTypeId,
            $pricing,
            $context->dto->trip->accessibility,
            $context->dto->returnTime
        );

        // Build price estimation using service
        $context->priceEstimation = $this->pricingService->buildPriceEstimation($context->totalPrice);

        return $next($context);
    }
}
