<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Setting\SettingEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Models\Setting;
use App\Models\TripLocation;
use App\Models\TripLocationStatusLog;
use Illuminate\Support\Collection;

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
     * Get waiting time rate from database settings
     */
    private function getWaitingTimeRate(): float
    {
        return (float) Setting::get(SettingEnum::WAITING_TIME_RATE);
    }

    /**
     * Get waiting time interval from database settings
     */
    private function getWaitingTimeIntervalMinutes(): int
    {
        return (int) Setting::get(SettingEnum::WAITING_TIME_INTERVAL_MINUTES);
    }

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
     * Charges for each COMPLETE interval only (based on database settings).
     * Example with 30-min interval and 2.500 KWD rate:
     *   31-59 minutes = 1 complete interval = 2.500 KWD
     *   60-89 minutes = 2 complete intervals = 5.000 KWD
     *
     * @return float|null Returns null if no waiting time, float if waiting time exists
     */
    public function calculateWaitingCharge(?int $returnTimeMinutes): ?float
    {
        if ($returnTimeMinutes === null || $returnTimeMinutes <= 0) {
            return null;
        }

        $intervalMinutes = $this->getWaitingTimeIntervalMinutes();
        $rate = $this->getWaitingTimeRate();

        // Use floor to charge only for COMPLETE intervals
        $intervals = floor($returnTimeMinutes / $intervalMinutes);

        // If no complete intervals yet, return null
        if ($intervals <= 0) {
            return null;
        }

        return round($intervals * $rate, 3);
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
     *
     * Note: For ROUND_TRIP (ride type 2), the round_trip_fee is NOT included in total_price
     * because a separate trip will be created and the price will be calculated when activated
     */
    public function calculateRoundTripPrice(
        float $distance,
        iterable $accessibilityRequirements = [],
        ?int $vehicleId = null,
        ?float $returnDistance = null
    ): array {
        // Calculate base fare for outbound journey (origin -> destination)
        $baseFare = $this->calculateBaseFare($distance, $vehicleId);
        $accessibilityCost = $this->calculateAccessibilityCost($accessibilityRequirements);

        // Calculate round trip fee for return journey (destination -> origin)
        // Use returnDistance if provided, otherwise use same distance
        $roundTripFee = $this->calculateBaseFare($returnDistance ?? $distance, $vehicleId);

        $totalPrice = $baseFare + ($accessibilityCost ?? 0.0); // Round trip fee NOT included

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
     *
     * Note: For ROUND_TRIP_WAIT (ride type 3), the waiting_charge is NOT included in total_price
     * because it's calculated at the end of the trip based on actual waiting time
     */
    public function calculateRoundTripWaitPrice(
        float $distance,
        ?int $returnTimeMinutes,
        iterable $accessibilityRequirements = [],
        ?int $vehicleId = null,
        ?float $returnDistance = null
    ): array {
        // Calculate base fare for outbound journey (origin -> destination)
        $baseFare = $this->calculateBaseFare($distance, $vehicleId);
        $accessibilityCost = $this->calculateAccessibilityCost($accessibilityRequirements);

        // Calculate round trip fee for return journey (destination -> origin)
        // Use returnDistance if provided, otherwise use same distance
        $roundTripFee = $this->calculateBaseFare($returnDistance ?? $distance, $vehicleId);

        $waitingCharge = $this->calculateWaitingCharge($returnTimeMinutes); // Calculated but not included in total
        $totalPrice = $baseFare + $roundTripFee + ($accessibilityCost ?? 0.0); // Waiting charge NOT included

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
        ?int $vehicleId = null,
        ?float $returnDistance = null
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
                $vehicleId,
                $returnDistance
            ),
            RideTypeEnum::ROUND_TRIP_WAIT => $this->calculateRoundTripWaitPrice(
                $distance,
                $returnTimeMinutes ?? 0,
                $accessibilityRequirements,
                $vehicleId,
                $returnDistance
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
            $value = priceFormat($pricing['round_trip_fee']) . ' ' . CurrencyEnum::KWD->getLabel();

            $breakdown[] = [
                'label' => trans('trips.api.breakdown.round_trip_fee'),
                'sub_label' => null,
                'value' => $value,
            ];
        }

        // Waiting time charge (for ROUND_TRIP_WAIT)
        // Always show "to be calculated" because waiting time is calculated at the end of the trip
        if ($rideType === RideTypeEnum::ROUND_TRIP_WAIT) {
            $breakdown[] = [
                'label' => trans('trips.api.breakdown.waiting_time_charge'),
                'sub_label' => null,
                'value' => trans('trips.api.breakdown.to_be_calculated'),
            ];
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
                // For ROUND_TRIP, display accessibility cost × 2 (used for both outbound and return)
                $displayedAccessibilityCost = $rideType === RideTypeEnum::ROUND_TRIP
                    ? $pricing['accessibility_cost'] * 2
                    : $pricing['accessibility_cost'];

                $breakdown[] = [
                    'label' => trans('trips.api.breakdown.accessibility_services'),
                    'sub_label' => $subLabel,
                    'value' => priceFormat($displayedAccessibilityCost) . ' ' . CurrencyEnum::KWD->getLabel(),
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
     *
     * For ROUND_TRIP, adds extra accessibility cost to display total (not stored in DB)
     */
    public function buildPriceEstimation(float $totalPrice, RideTypeEnum $rideType, ?float $accessibilityCost = null): array
    {
        // Use "Price Estimation" for ROUND_TRIP_WAIT, "Price" for other ride types
        $label = $rideType === RideTypeEnum::ROUND_TRIP_WAIT
            ? trans('trips.api.price_estimation')
            : trans('trips.api.price');

        // For ROUND_TRIP, add extra accessibility cost for display (doubled accessibility)
        $displayTotalPrice = $totalPrice;
        if ($rideType === RideTypeEnum::ROUND_TRIP && $accessibilityCost !== null && $accessibilityCost > 0) {
            $displayTotalPrice = $totalPrice + $accessibilityCost;
        }

        return [
            'label' => $label,
            'value' => priceFormat($displayTotalPrice) . ' ' . CurrencyEnum::KWD->getLabel(),
        ];
    }

    /**
     * Get waiting time configuration
     */
    public function getWaitingTimeConfig(): array
    {
        return [
            'price' => priceFormat($this->getWaitingTimeRate()) . ' ' . CurrencyEnum::KWD->getLabel(),
            'time' => $this->getWaitingTimeIntervalMinutes() . ' ' . trans('trips.api.time_units.minutes'),
        ];
    }

    /**
     * Calculate actual waiting time from completed trip locations
     * For ROUND_TRIP_WAIT: calculates time between first destination DROPPED_OFF and second destination/origin PICKED_UP
     *
     * @param  Collection  $locations  Trip locations with statusLogs relationship loaded
     * @return int|null Waiting time in minutes, or null if you cannot be calculated
     */
    public function calculateActualWaitingTime(Collection $locations): ?int
    {
        // Find first destination that was dropped off
        $firstDestination = $locations->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)->first();

        if (! $firstDestination) {
            return null;
        }

        // Find the DROPPED_OFF status log for first destination
        $droppedOffLog = $firstDestination->statusLogs
            ->firstWhere(TripLocationStatusLog::COLUMN_STATUS, TripLocationStatusEnum::DROPPED_OFF);

        if (! $droppedOffLog) {
            return null;
        }

        // Find the next location after first destination (should be sequence + 1)
        $nextLocation = $locations->where('sequence', '>', $firstDestination->sequence)
            ->sortBy('sequence')
            ->first();

        if (! $nextLocation) {
            return null;
        }

        // Find the PICKED_UP status log for the next location
        $pickedUpLog = $nextLocation->statusLogs
            ->firstWhere('status', TripLocationStatusEnum::PICKED_UP);

        if (! $pickedUpLog) {
            return null;
        }

        // Calculate difference in minutes
        $waitingMinutes = $droppedOffLog->created_at->diffInMinutes($pickedUpLog->created_at);

        return (int) $waitingMinutes;
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
