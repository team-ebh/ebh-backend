<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Services\PriceBreakdownService;
use Closure;

/**
 * Build Price Breakdown Pipe
 *
 * Builds price breakdown and estimation response data
 */
readonly class BuildPriceBreakdownPipe
{
    public function __construct(
        private PriceBreakdownService $priceBreakdownService
    ) {}

    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        // Build price breakdown
        $context->priceBreakdown = $this->priceBreakdownService->buildTripStoreBreakdown(
            $context->baseFare,
            $context->dto->accessibilityRequirements,
            $context->accessibilityCost
        );

        // Build price estimation
        $context->priceEstimation = $this->priceBreakdownService->buildPriceEstimation(
            $context->estimatedPrice
        );

        return $next($context);
    }
}
