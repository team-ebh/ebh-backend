<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\RideTypeEnum;

/**
 * Trip Pricing Service
 *
 * Handles dynamic trip pricing calculations based on:
 * - Vehicle per-km rate × distance
 * - Waiting time charges (for ROUND_TRIP_WAIT)
 * - Accessibility add-ons
 * - Ride type multipliers (ONE_WAY, ROUND_TRIP, ROUND_TRIP_WAIT)
 */
class TripPricingService
{
    /**
     * Default pricing configuration
     * TODO: Replace with Vehicle model rates when available
     */
    private const float BASE_FARE = 2.000; // Base fare in KWD

    private const float PER_KM_RATE = 0.500; // Rate per kilometer in KWD

    /**
     * Waiting time configuration
     */
    private const float WAITING_TIME_RATE_PER_30_MIN = 2.500; // KWD per 30 minutes

    private const int WAITING_TIME_INTERVAL_MINUTES = 30;

    /**
     * Calculate base fare from distance
     *
     * Formula: BASE_FARE + (distance × PER_KM_RATE)
     * TODO: Use vehicle-specific per-km rate when Vehicle model is available
     */
    public function calculateBaseFare(float $distance, ?int $vehicleId = null): float
    {
        // TODO: When Vehicle model is available, fetch per_km_rate from vehicle
        // $vehicle = Vehicle::find($vehicleId);
        // $perKmRate = $vehicle->per_km_rate;

        $perKmRate = self::PER_KM_RATE;

        return round(self::BASE_FARE + ($distance * $perKmRate), 3);
    }

    /**
     * Calculate accessibility cost from requirements
     *
     * @return float|null Returns null if no accessibility requirements, float if any exist
     */
    public function calculateAccessibilityCost(iterable $accessibilityRequirements): ?float
    {
        if (empty($accessibilityRequirements)) {
            return null;
        }

        $totalCost = 0.0;
        foreach ($accessibilityRequirements as $requirement) {
            // Handle both Enum instances and model relationships
            if (method_exists($requirement, 'getPrice')) {
                $price = $requirement->getPrice();
            } elseif (isset($requirement->accessibility_requirement)) {
                $price = $requirement->accessibility_requirement->getPrice();
            } else {
                continue;
            }

            if ($price !== null) {
                $totalCost += $price;
            }
        }

        return round($totalCost, 3);
    }

    /**
     * Calculate waiting time charge
     *
     * @return float|null Returns null if no waiting time, float if waiting time exists
     */
    public function calculateWaitingCharge(?int $returnTimeMinutes): ?float
    {
        if ($returnTimeMinutes === null || $returnTimeMinutes <= 0) {
            return null;
        }

        $intervals = ceil($returnTimeMinutes / self::WAITING_TIME_INTERVAL_MINUTES);

        return round($intervals * self::WAITING_TIME_RATE_PER_30_MIN, 3);
    }

    /**
     * Calculate total trip price for ONE_WAY ride type
     */
    public function calculateOneWayPrice(
        float $distance,
        iterable $accessibilityRequirements = [],
        ?int $vehicleId = null
    ): array {
        $baseFare = $this->calculateBaseFare($distance, $vehicleId);
        $accessibilityCost = $this->calculateAccessibilityCost($accessibilityRequirements);
        $totalPrice = $baseFare + ($accessibilityCost ?? 0.0);

        return [
            'base_fare' => $baseFare,
            'accessibility_cost' => $accessibilityCost,
            'round_trip_fee' => null,
            'waiting_charge' => null,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Calculate total trip price for ROUND_TRIP ride type
     */
    public function calculateRoundTripPrice(
        float $distance,
        iterable $accessibilityRequirements = [],
        ?int $vehicleId = null
    ): array {
        $baseFare = $this->calculateBaseFare($distance, $vehicleId);
        $accessibilityCost = $this->calculateAccessibilityCost($accessibilityRequirements);
        $roundTripFee = $baseFare; // Round trip fee equals base fare
        $totalPrice = $baseFare + $roundTripFee + ($accessibilityCost ?? 0.0);

        return [
            'base_fare' => $baseFare,
            'accessibility_cost' => $accessibilityCost,
            'round_trip_fee' => $roundTripFee,
            'waiting_charge' => null,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Calculate total trip price for ROUND_TRIP_WAIT ride type
     */
    public function calculateRoundTripWaitPrice(
        float $distance,
        ?int $returnTimeMinutes,
        iterable $accessibilityRequirements = [],
        ?int $vehicleId = null
    ): array {
        $baseFare = $this->calculateBaseFare($distance, $vehicleId);
        $accessibilityCost = $this->calculateAccessibilityCost($accessibilityRequirements);
        $roundTripFee = $baseFare;
        $waitingCharge = $this->calculateWaitingCharge($returnTimeMinutes);
        $totalPrice = $baseFare + $roundTripFee + ($waitingCharge ?? 0.0) + ($accessibilityCost ?? 0.0);

        return [
            'base_fare' => $baseFare,
            'accessibility_cost' => $accessibilityCost,
            'round_trip_fee' => $roundTripFee,
            'waiting_charge' => $waitingCharge,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Calculate price based on ride type (main method)
     */
    public function calculatePriceByRideType(
        RideTypeEnum $rideType,
        float $distance,
        iterable $accessibilityRequirements = [],
        ?int $returnTimeMinutes = null,
        ?int $vehicleId = null
    ): array {
        return match ($rideType) {
            RideTypeEnum::ONE_WAY => $this->calculateOneWayPrice(
                $distance,
                $accessibilityRequirements,
                $vehicleId
            ),
            RideTypeEnum::ROUND_TRIP => $this->calculateRoundTripPrice(
                $distance,
                $accessibilityRequirements,
                $vehicleId
            ),
            RideTypeEnum::ROUND_TRIP_WAIT => $this->calculateRoundTripWaitPrice(
                $distance,
                $returnTimeMinutes ?? 0,
                $accessibilityRequirements,
                $vehicleId
            ),
        };
    }

    /**
     * Build price breakdown for display
     */
    public function buildPriceBreakdown(
        RideTypeEnum $rideType,
        array $pricing,
        iterable $accessibilityRequirements = [],
        ?int $returnTimeMinutes = null
    ): array {
        $breakdown = [];

        // Base fare
        $breakdown[] = [
            'label' => trans('trips.api.breakdown.base_fare'),
            'sub_label' => null,
            'value' => priceFormat($pricing['base_fare']) . ' ' . CurrencyEnum::KWD->getLabel(),
        ];

        // Round trip fee (for ROUND_TRIP and ROUND_TRIP_WAIT)
        if ($rideType === RideTypeEnum::ROUND_TRIP || $rideType === RideTypeEnum::ROUND_TRIP_WAIT) {
            $breakdown[] = [
                'label' => trans('trips.api.breakdown.round_trip_fee'),
                'sub_label' => null,
                'value' => priceFormat($pricing['round_trip_fee']) . ' ' . CurrencyEnum::KWD->getLabel(),
            ];
        }

        // Waiting time charge (for ROUND_TRIP_WAIT)
        if ($rideType === RideTypeEnum::ROUND_TRIP_WAIT) {
            if ($pricing['waiting_charge'] !== null) {
                $breakdown[] = [
                    'label' => trans('trips.api.breakdown.waiting_time_charge'),
                    'sub_label' => null,
                    'value' => priceFormat($pricing['waiting_charge']) . ' ' . CurrencyEnum::KWD->getLabel(),
                ];
            } else {
                $breakdown[] = [
                    'label' => trans('trips.api.breakdown.waiting_time_charge'),
                    'sub_label' => null,
                    'value' => trans('trips.api.breakdown.to_be_calculated'),
                ];
            }
        }

        // Accessibility services
        if ($pricing['accessibility_cost'] !== null || ! empty($accessibilityRequirements)) {
            $accessibilityNames = [];
            foreach ($accessibilityRequirements as $requirement) {
                if (method_exists($requirement, 'getLabel')) {
                    $accessibilityNames[] = $requirement->getLabel();
                } elseif (isset($requirement->accessibility_requirement)) {
                    $accessibilityNames[] = $requirement->accessibility_requirement->getLabel();
                }
            }

            $subLabel = ! empty($accessibilityNames) ? implode(', ', $accessibilityNames) : null;

            if ($pricing['accessibility_cost'] !== null && $pricing['accessibility_cost'] > 0) {
                $breakdown[] = [
                    'label' => trans('trips.api.breakdown.accessibility_services'),
                    'sub_label' => $subLabel,
                    'value' => priceFormat($pricing['accessibility_cost']) . ' ' . CurrencyEnum::KWD->getLabel(),
                ];
            } elseif (! empty($accessibilityNames)) {
                $breakdown[] = [
                    'label' => trans('trips.api.breakdown.accessibility_services'),
                    'sub_label' => $subLabel,
                    'value' => trans('trips.api.breakdown.included'),
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Build price estimation
     */
    public function buildPriceEstimation(float $totalPrice): array
    {
        return [
            'label' => trans('trips.api.price_estimation'),
            'value' => priceFormat($totalPrice) . ' ' . CurrencyEnum::KWD->getLabel(),
        ];
    }

    /**
     * Get waiting time configuration
     */
    public function getWaitingTimeConfig(): array
    {
        return [
            'price' => priceFormat(self::WAITING_TIME_RATE_PER_30_MIN) . ' ' . CurrencyEnum::KWD->getLabel(),
            'time' => self::WAITING_TIME_INTERVAL_MINUTES . ' ' . trans('trips.api.time_units.minutes'),
        ];
    }

    /**
     * Calculate distance using Haversine formula
     */
    public function calculateDistance(
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
