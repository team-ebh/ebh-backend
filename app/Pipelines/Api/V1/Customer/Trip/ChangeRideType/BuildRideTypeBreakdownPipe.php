<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Services\PriceBreakdownService;
use Closure;

/**
 * Build Ride Type Breakdown Pipe
 *
 * Builds price breakdown, estimation, and waiting time config based on ride type
 */
class BuildRideTypeBreakdownPipe
{
    /**
     * Waiting time rate configuration
     */
    private const float WAITING_TIME_RATE_PER_30_MIN = 2.500;

    private const int WAITING_TIME_INTERVAL_MINUTES = 30;

    public function __construct(
        private readonly PriceBreakdownService $priceBreakdownService
    ) {}

    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Add waiting time config if needed
        if ($context->dto->rideTypeId->needsWaitingTimeConfig()) {
            $context->waitingTimeConfig = [
                'price' => priceFormat(self::WAITING_TIME_RATE_PER_30_MIN) . ' ' . CurrencyEnum::KWD->getLabel(),
                'time' => self::WAITING_TIME_INTERVAL_MINUTES . ' ' . trans('trips.api.time_units.minutes'),
            ];
        }

        // If no location data, return empty breakdown
        if ($context->distance === null) {
            $context->priceBreakdown = [];
            $context->priceEstimation = null;

            return $next($context);
        }

        // Build price breakdown based on ride type
        $breakdown = match ($context->dto->rideTypeId) {
            RideTypeEnum::ONE_WAY => $this->buildOneWayBreakdown($context),
            RideTypeEnum::ROUND_TRIP => $this->buildRoundTripBreakdown($context),
            RideTypeEnum::ROUND_TRIP_WAIT => $this->buildRoundTripWaitBreakdown($context),
        };

        // Add accessibility breakdown
        $accessibilityBreakdown = $this->priceBreakdownService->buildAccessibilityBreakdown(
            $context->dto->trip->accessibility,
            $context->accessibilityCost
        );

        $context->priceBreakdown = array_merge($breakdown, $accessibilityBreakdown);

        // Build price estimation
        $context->priceEstimation = $this->priceBreakdownService->buildPriceEstimation($context->totalPrice);

        return $next($context);
    }

    /**
     * Build price breakdown for ONE_WAY ride type
     */
    private function buildOneWayBreakdown(ChangeRideTypeContext $context): array
    {
        return [
            [
                'label' => trans('trips.api.breakdown.base_fare'),
                'sub_label' => null,
                'value' => priceFormat($context->baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];
    }

    /**
     * Build price breakdown for ROUND_TRIP ride type
     */
    private function buildRoundTripBreakdown(ChangeRideTypeContext $context): array
    {
        return [
            [
                'label' => trans('trips.api.breakdown.base_fare'),
                'sub_label' => null,
                'value' => priceFormat($context->baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
            [
                'label' => trans('trips.api.breakdown.round_trip_fee'),
                'sub_label' => null,
                'value' => priceFormat($context->roundTripFee) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];
    }

    /**
     * Build price breakdown for ROUND_TRIP_WAIT ride type
     */
    private function buildRoundTripWaitBreakdown(ChangeRideTypeContext $context): array
    {
        $breakdown = [
            [
                'label' => trans('trips.api.breakdown.base_fare'),
                'sub_label' => null,
                'value' => priceFormat($context->baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
            [
                'label' => trans('trips.api.breakdown.round_trip_fee'),
                'sub_label' => null,
                'value' => priceFormat($context->roundTripFee) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];

        // Add waiting time charge
        if ($context->dto->returnTime !== null && $context->dto->returnTime > 0) {
            $breakdown[] = [
                'label' => trans('trips.api.breakdown.waiting_time_charge'),
                'sub_label' => null,
                'value' => priceFormat($context->waitingCharge) . ' ' . CurrencyEnum::KWD->getLabel(),
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
