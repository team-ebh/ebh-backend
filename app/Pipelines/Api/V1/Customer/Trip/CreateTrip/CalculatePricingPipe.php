<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use Closure;

/**
 * Calculate Pricing Pipe
 *
 * Calculates base fare and estimated price based on distance
 */
class CalculatePricingPipe
{
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        $context->baseFare = $this->calculateBaseFare($context);
        $context->accessibilityCost = $this->calculateAccessibilityCost($context);
        $context->estimatedPrice = $this->calculateEstimatedPrice($context->baseFare, $context);

        return $next($context);
    }

    /**
     * Calculate base fare based on distance
     * TODO: Implement actual pricing calculation logic
     */
    private function calculateBaseFare(TripCreationContext $context): float
    {
        // Calculate distance using Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($context->dto->originLatitude);
        $lonFrom = deg2rad($context->dto->originLongitude);
        $latTo = deg2rad($context->dto->destinationLatitude);
        $lonTo = deg2rad($context->dto->destinationLongitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $angle * $earthRadius;

        // Simple pricing: 2 KWD base + 0.5 KWD per km
        return round(2.0 + ($distance * 0.5), 3);
    }

    /**
     * Calculate accessibility cost based on requirements
     */
    private function calculateAccessibilityCost(TripCreationContext $context): float
    {
        if (empty($context->dto->accessibilityRequirements)) {
            return 0.0;
        }

        $totalCost = 0.0;
        foreach ($context->dto->accessibilityRequirements as $requirement) {
            $price = $requirement->getPrice();
            if ($price !== null) {
                $totalCost += $price;
            }
        }

        return round($totalCost, 3);
    }

    /**
     * Calculate estimated price based on base fare and trip details
     * TODO: Implement actual pricing logic (surge, ride type multiplier, etc.)
     */
    private function calculateEstimatedPrice(float $baseFare, TripCreationContext $context): float
    {
        // Include accessibility cost in the estimated price
        return round($baseFare + $context->accessibilityCost, 3);
    }
}
