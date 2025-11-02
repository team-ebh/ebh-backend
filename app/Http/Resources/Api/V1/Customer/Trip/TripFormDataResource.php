<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripFormDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Available trip types
             *
             * @var TripTypeResource
             */
            'trip_types' => TripTypeResource::collection($this->resource['trip_types']),

            /**
             * Available vehicle types
             *
             * @var TripVehicleTypeResource
             */
            'vehicle_types' => TripVehicleTypeResource::collection($this->resource['vehicle_types']),

            /**
             * Available accessibility requirements
             *
             * @var AccessibilityRequirementsResource
             */
            'accessibility_requirements' => AccessibilityRequirementsResource::collection($this->resource['accessibility_requirements']),

            /**
             * @example 1
             *
             * @var int
             */
            'maximum_passengers' => $this->resource['maximum_passengers'],
        ];
    }
}
