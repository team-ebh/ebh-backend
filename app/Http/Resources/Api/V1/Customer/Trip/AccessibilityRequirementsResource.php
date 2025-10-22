<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessibilityRequirementsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var AccessibilityRequirementsEnum $accessibilityRequirement
         */
        $accessibilityRequirement = $this->resource;

        return [
            /**
             * Accessibility requirement identifier
             *
             * @example 1
             *
             * @var int
             */
            'id' => $accessibilityRequirement->value,

            /**
             * Accessibility requirement label (translated based on request language)
             *
             * @example "Wheelchair Accessible"
             *
             * @var string
             */
            'label' => $accessibilityRequirement->getLabel(),

            /**
             * Accessibility requirement description (translated based on request language)
             *
             * @example "Standard Wheelchair transport"
             *
             * @var string
             */
            'description' => $accessibilityRequirement->getDescription(),
        ];
    }
}
