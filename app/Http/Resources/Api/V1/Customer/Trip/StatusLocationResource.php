<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Status Location Resource
 *
 * Formats trip location information for trip status response
 */
class StatusLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Location ID
             *
             * @example 12
             *
             * @var int
             */
            'id' => $this->resource['id'],

            /**
             * Location Type
             *
             * Type of location in the trip
             *
             * @var LocationTypeResource
             */
            'type' => new LocationTypeResource($this->resource['type']),

            /**
             * Location Title
             *
             * Main title/name of the location
             *
             * @example "Kuwait International Airport"
             *
             * @var string|null
             */
            'location_title' => $this->resource['location_title'],

            /**
             * Location Subtitle
             *
             * Additional location details
             *
             * @example "Terminal 4"
             *
             * @var string|null
             */
            'location_sub_title' => $this->resource['location_sub_title'],

            /**
             * Latitude
             *
             * @example 29.226567
             *
             * @var float
             */
            'latitude' => $this->resource['latitude'],

            /**
             * Longitude
             *
             * @example 47.968928
             *
             * @var float
             */
            'longitude' => $this->resource['longitude'],

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
