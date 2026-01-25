<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Models\VehicleAccessibilityFeature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Accessibility Feature Resource
 *
 * Formats accessibility feature information for vehicle response
 */
class AccessibilityFeatureResource extends JsonResource
{
    /**
     * @var VehicleAccessibilityFeature
     */
    public $resource;

    public function toArray(Request $request): array
    {
        $accessibilityRequirement = AccessibilityRequirementsEnum::tryFrom(
            $this->resource->{VehicleAccessibilityFeature::COLUMN_ACCESSIBILITY_REQUIREMENT_ID}
        );

        return [
            /**
             * Accessibility requirement identifier
             *
             * @example 1
             *
             * @var int
             */
            'id' => $accessibilityRequirement?->value,

            /**
             * Accessibility requirement label (translated based on request language)
             *
             * @example "Wheelchair Accessible"
             *
             * @var string|null
             */
            'label' => $accessibilityRequirement?->getLabel(),

            /**
             * Accessibility requirement description (translated based on request language)
             *
             * @example "Standard Wheelchair transport"
             *
             * @var string|null
             */
            'description' => $accessibilityRequirement?->getDescription(),

            /**
             * Accessibility requirement icon URL
             *
             * @example "http://api.ebhapp.com/images/trip/types/wheelchair.svg"
             *
             * @var string|null
             */
            'icon' => $accessibilityRequirement?->getIcon(),
        ];
    }
}
