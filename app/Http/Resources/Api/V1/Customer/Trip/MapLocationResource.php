<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Map Location Resource
 *
 * Formats location coordinates for map display (simplified format)
 */
class MapLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Latitude
             *
             * Geographic latitude coordinate
             *
             * @example 29.226567
             *
             * @var float
             */
            'latitude' => $this->resource['latitude'],

            /**
             * Longitude
             *
             * Geographic longitude coordinate
             *
             * @example 47.968928
             *
             * @var float
             */
            'longitude' => $this->resource['longitude'],

            /**
             * Type
             *
             * Location type (1: origin, 2: destination)
             *
             * @example 1
             *
             * @var int
             */
            'type' => new LocationTypeResource($this->resource['type']),

            /**
             * Sequence
             *
             * Order of location in the trip route
             *
             * @example 1
             *
             * @var int
             */
            'sequence' => $this->resource['sequence'],
        ];
    }
}
