<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Enums\Trip\RideTypeEnum;
use Closure;

/**
 * Calculate Ride Pricing Pipe
 *
 * Calculates pricing based on ride type (ONE_WAY, ROUND_TRIP, ROUND_TRIP_WAIT)
 */
class CalculateRidePricingPipe
{
    /**
     * Waiting time rate configuration
     * Price per 30 minutes of waiting
     */
    private const float WAITING_TIME_RATE_PER_30_MIN = 2.500;

    private const int WAITING_TIME_INTERVAL_MINUTES = 30;

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Skip pricing calculation if no distance (no location data)
        if ($context->distance === null) {
            return $next($context);
        }

        // Calculate base fare
        $context->baseFare = $this->calculateBaseFare($context->distance);

        // Calculate accessibility cost
        $context->accessibilityCost = $this->calculateAccessibilityCost($context);

        // Calculate pricing based on ride type
        match ($context->dto->rideTypeId) {
            RideTypeEnum::ONE_WAY => $this->calculateOneWay($context),
            RideTypeEnum::ROUND_TRIP => $this->calculateRoundTrip($context),
            RideTypeEnum::ROUND_TRIP_WAIT => $this->calculateRoundTripWait($context),
        };

        return $next($context);
    }

    /**
     * Calculate base fare based on distance
     */
    private function calculateBaseFare(float $distance): float
    {
        // Simple pricing: 2 KWD base + 0.5 KWD per km
        return round(2.0 + ($distance * 0.5), 3);
    }

    /**
     * Calculate accessibility cost based on trip's accessibility requirements
     */
    private function calculateAccessibilityCost(ChangeRideTypeContext $context): float
    {
        if ($context->dto->trip->accessibility->isEmpty()) {
            return 0.0;
        }

        $totalCost = 0.0;
        foreach ($context->dto->trip->accessibility as $accessibility) {
            $price = $accessibility->accessibility_requirement->getPrice();
            if ($price !== null) {
                $totalCost += $price;
            }
        }

        return round($totalCost, 3);
    }

    /**
     * Calculate ONE_WAY pricing
     */
    private function calculateOneWay(ChangeRideTypeContext $context): void
    {
        $context->roundTripFee = 0.0;
        $context->waitingCharge = 0.0;
        $context->totalPrice = $context->baseFare + $context->accessibilityCost;
    }

    /**
     * Calculate ROUND_TRIP pricing
     */
    private function calculateRoundTrip(ChangeRideTypeContext $context): void
    {
        $context->roundTripFee = $context->baseFare;
        $context->waitingCharge = 0.0;
        $context->totalPrice = $context->baseFare + $context->roundTripFee + $context->accessibilityCost;
    }

    /**
     * Calculate ROUND_TRIP_WAIT pricing
     */
    private function calculateRoundTripWait(ChangeRideTypeContext $context): void
    {
        $context->roundTripFee = $context->baseFare;
        $context->waitingCharge = $this->calculateWaitingCharge($context->dto->returnTime ?? 0);
        $context->totalPrice = $context->baseFare + $context->roundTripFee + $context->waitingCharge + $context->accessibilityCost;
    }

    /**
     * Calculate waiting time charge
     */
    private function calculateWaitingCharge(int $returnTime): float
    {
        if ($returnTime <= 0) {
            return 0.0;
        }

        $intervals = ceil($returnTime / self::WAITING_TIME_INTERVAL_MINUTES);

        return round($intervals * self::WAITING_TIME_RATE_PER_30_MIN, 3);
    }
}
