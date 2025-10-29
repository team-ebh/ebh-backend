<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\ChangeRideTypeDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Services\PriceBreakdownService;

/**
 * Change Ride Type Action
 *
 * Calculates pricing based on ride type without creating a trip
 */
class ChangeRideTypeAction
{
    /**
     * Waiting time rate configuration
     * Price per 30 minutes of waiting
     */
    private const float WAITING_TIME_RATE_PER_30_MIN = 2.500;

    private const int WAITING_TIME_INTERVAL_MINUTES = 30;

    public function __construct(
        private readonly PriceBreakdownService $priceBreakdownService
    ) {}

    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(ChangeRideTypeDTO $dto): array
    {
        // Load accessibility requirements for the trip
        $dto->trip->load('accessibility');

        return safeProcess()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'calculateRidePrice'], $dto);
    }

    /**
     * Calculate ride price based on ride type
     *
     * @throws \Throwable
     */
    public function calculateRidePrice(ChangeRideTypeDTO $dto): array
    {
        $result = [];

        // Add waiting time config only for ride types that need it
        if ($dto->rideTypeId->needsWaitingTimeConfig()) {
            $result['waiting_time_config'] = [
                'price' => priceFormat(self::WAITING_TIME_RATE_PER_30_MIN) . ' ' . CurrencyEnum::KWD->getLabel(),
                'time' => self::WAITING_TIME_INTERVAL_MINUTES,
            ];
        }

        // If location data is not provided, return only waiting time config
        if ($dto->originLatitude === null || $dto->originLongitude === null ||
            $dto->destinationLatitude === null || $dto->destinationLongitude === null) {
            $result['price_breakdown'] = [];
            $result['price_estimation'] = null;

            return $result;
        }

        $distance = $this->calculateDistance(
            $dto->originLatitude,
            $dto->originLongitude,
            $dto->destinationLatitude,
            $dto->destinationLongitude
        );

        $baseFare = $this->calculateBaseFare($distance);
        $accessibilityCost = $this->calculateAccessibilityCost($dto);
        $priceBreakdown = [];
        $totalPrice = $baseFare + $accessibilityCost;
        $roundTripFee = 0.0;
        $waitingCharge = 0.0;

        // Build price breakdown based on ride type
        match ($dto->rideTypeId) {
            RideTypeEnum::ONE_WAY => [
                // ONE_WAY: Base fare only
                $priceBreakdown = $this->buildOneWayBreakdown($baseFare),
                $totalPrice = $baseFare + $accessibilityCost,
            ],
            RideTypeEnum::ROUND_TRIP => [
                // ROUND_TRIP: Base fare + Round Trip fee
                $roundTripFee = $baseFare, // Round trip fee equals base fare
                $priceBreakdown = $this->buildRoundTripBreakdown($baseFare, $roundTripFee),
                $totalPrice = $baseFare + $roundTripFee + $accessibilityCost,
            ],
            RideTypeEnum::ROUND_TRIP_WAIT => [
                // ROUND_TRIP_WAIT: Base fare + Round Trip fee + Wait Time Charge
                $roundTripFee = $baseFare,
                $waitingCharge = $this->calculateWaitingCharge($dto->waitingTimeMinutes ?? 0),
                $priceBreakdown = $this->buildRoundTripWaitBreakdown($baseFare, $roundTripFee, $waitingCharge, $dto->waitingTimeMinutes ?? null),
                $totalPrice = $baseFare + $roundTripFee + $waitingCharge + $accessibilityCost,
            ],
        };

        // Add accessibility cost to breakdown using service
        $accessibilityBreakdown = $this->priceBreakdownService->buildAccessibilityBreakdown(
            $dto->trip->accessibility,
            $accessibilityCost
        );

        $priceBreakdown = array_merge($priceBreakdown, $accessibilityBreakdown);

        $result['price_breakdown'] = $priceBreakdown;
        $result['price_estimation'] = $this->priceBreakdownService->buildPriceEstimation($totalPrice);

        return $result;
    }

    /**
     * Calculate distance using Haversine formula
     */
    private function calculateDistance(
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

    /**
     * Calculate base fare based on distance
     */
    private function calculateBaseFare(float $distance): float
    {
        // Simple pricing: 2 KWD base + 0.5 KWD per km
        return round(2.0 + ($distance * 0.5), 3);
    }

    /**
     * Calculate waiting time charge
     */
    private function calculateWaitingCharge(int $waitingTimeMinutes): float
    {
        if ($waitingTimeMinutes <= 0) {
            return 0.0;
        }

        $intervals = ceil($waitingTimeMinutes / self::WAITING_TIME_INTERVAL_MINUTES);

        return round($intervals * self::WAITING_TIME_RATE_PER_30_MIN, 3);
    }

    /**
     * Calculate accessibility cost based on trip's accessibility requirements
     */
    private function calculateAccessibilityCost(ChangeRideTypeDTO $dto): float
    {
        if ($dto->trip->accessibility->isEmpty()) {
            return 0.0;
        }

        $totalCost = 0.0;
        foreach ($dto->trip->accessibility as $accessibility) {
            $price = $accessibility->accessibility_requirement->getPrice();
            if ($price !== null) {
                $totalCost += $price;
            }
        }

        return round($totalCost, 3);
    }

    /**
     * Build price breakdown for ONE_WAY ride type
     */
    private function buildOneWayBreakdown(float $baseFare): array
    {
        return [
            [
                'label' => trans('trips.api.breakdown.base_fare'),
                'sub_label' => null,
                'value' => priceFormat($baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];
    }

    /**
     * Build price breakdown for ROUND_TRIP ride type
     */
    private function buildRoundTripBreakdown(float $baseFare, float $roundTripFee): array
    {
        return [
            [
                'label' => trans('trips.api.breakdown.base_fare'),
                'sub_label' => null,
                'value' => priceFormat($baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
            [
                'label' => trans('trips.api.breakdown.round_trip_fee'),
                'sub_label' => null,
                'value' => priceFormat($roundTripFee) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];
    }

    /**
     * Build price breakdown for ROUND_TRIP_WAIT ride type
     */
    private function buildRoundTripWaitBreakdown(float $baseFare, float $roundTripFee, float $waitingCharge, ?int $waitingTimeMinutes): array
    {
        $breakdown = [
            [
                'label' => trans('trips.api.breakdown.base_fare'),
                'sub_label' => null,
                'value' => priceFormat($baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
            [
                'label' => trans('trips.api.breakdown.round_trip_fee'),
                'sub_label' => null,
                'value' => priceFormat($roundTripFee) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];

        // Add waiting time charge
        if ($waitingTimeMinutes !== null && $waitingTimeMinutes > 0) {
            $breakdown[] = [
                'label' => trans('trips.api.breakdown.waiting_time_charge'),
                'sub_label' => null,
                'value' => priceFormat($waitingCharge) . ' ' . CurrencyEnum::KWD->getLabel(),
            ];
        } else {
            $breakdown[] = [
                'label' => trans('trips.api.breakdown.waiting_time_charge'),
                'sub_label' => null,
                'value' => trans('trips.api.breakdown.to_be_calculated'),
            ];
        }

        return $breakdown;
    }
}
