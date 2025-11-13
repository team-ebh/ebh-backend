<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Location Point Resource
 *
 * Formats a single location point with full details
 */
class LocationPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
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
             * Additional details of the location
             *
             * @example "Terminal 4"
             *
             * @var string|null
             */
            'location_sub_title' => $this->resource['location_sub_title'],

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
        ];
    }
}
