<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Enums\Trip\RideTypeEnum;
use App\Services\TripPricingService;
use Closure;

/**
 * Build Price Breakdown Pipe
 *
 * Builds price breakdown and estimation response data using TripPricingService
 */
readonly class BuildPriceBreakdownPipe
{
    public function __construct(
        private TripPricingService $pricingService
    ) {}

    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        // Build pricing array for breakdown
        $pricing = [
            'base_fare' => $context->baseFare,
            'accessibility_cost' => $context->accessibilityCost,
            'round_trip_fee' => null,
            'waiting_charge' => null,
            'total_price' => $context->estimatedPrice,
        ];

        // Build price breakdown using service
        $context->priceBreakdown = $this->pricingService->buildPriceBreakdown(
            RideTypeEnum::ONE_WAY,
            $pricing,
            $context->dto->accessibilityRequirements ?? []
        );

        // Build price estimation using service
        $context->priceEstimation = $this->pricingService->buildPriceEstimation(
            $context->estimatedPrice
        );

        return $next($context);
    }
}
