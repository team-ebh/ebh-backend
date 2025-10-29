<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
use Illuminate\Support\Collection;

/**
 * Price Breakdown Service
 *
 * Handles price breakdown and estimation formatting for trips
 */
class PriceBreakdownService
{
    /**
     * Build price breakdown with base fare
     */
    public function buildBaseFareBreakdown(float $baseFare): array
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
     * Build accessibility breakdown from enum instances or model collection
     *
     * @param  array|Collection  $accessibilityRequirements  Array of AccessibilityRequirementsEnum or Collection of TripAccessibility models
     */
    public function buildAccessibilityBreakdown(Collection | array $accessibilityRequirements, float $accessibilityCost): array
    {
        if (empty($accessibilityRequirements)) {
            return [];
        }

        // Convert to array of labels
        $accessibilityNames = [];
        foreach ($accessibilityRequirements as $requirement) {
            if ($requirement instanceof AccessibilityRequirementsEnum) {
                $accessibilityNames[] = $requirement->getLabel();
            } elseif (isset($requirement->accessibility_requirement)) {
                // It's a TripAccessibility model
                $accessibilityNames[] = $requirement->accessibility_requirement->getLabel();
            }
        }

        if (empty($accessibilityNames)) {
            return [];
        }

        $subLabel = implode(', ', $accessibilityNames);

        if ($accessibilityCost > 0) {
            return [
                [
                    'label' => trans('trips.api.breakdown.accessibility_services'),
                    'sub_label' => $subLabel,
                    'value' => priceFormat($accessibilityCost) . ' ' . CurrencyEnum::KWD->getLabel(),
                ],
            ];
        }

        return [
            [
                'label' => trans('trips.api.breakdown.accessibility_services'),
                'sub_label' => $subLabel,
                'value' => trans('trips.api.breakdown.included'),
            ],
        ];
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
     * Build complete price breakdown for trip store
     */
    public function buildTripStoreBreakdown(
        float $baseFare,
        $accessibilityRequirements,
        float $accessibilityCost
    ): array {
        $breakdown = $this->buildBaseFareBreakdown($baseFare);

        $accessibilityBreakdown = $this->buildAccessibilityBreakdown(
            $accessibilityRequirements,
            $accessibilityCost
        );

        return array_merge($breakdown, $accessibilityBreakdown);
    }
}
