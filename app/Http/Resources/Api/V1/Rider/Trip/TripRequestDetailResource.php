<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip;

use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Resource
 *
 * Formats trip data for rider API responses
 *
 * @property Trip $resource
 */
class TripRequestDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip Request ID
             *
             * @var int
             *
             * @example 123
             */
            'trip_request_id' => $this->resource['trip_request_id'],

            /**
             * Trip ID
             *
             * @var int
             *
             * @example 456
             */
            'trip_id' => $this->resource['trip_id'],

            /**
             * Trip distance (Meters)
             *
             * @var int
             *
             * @example 325
             */
            'distance' => $this->resource['distance'],

            /**
             * Trip eta (seconds)
             *
             * @var int
             *
             * @example 210
             */
            'eta' => $this->resource['eta'],

            /**
             * Trip arrive time (unix timestamp)
             *
             * @var int
             *
             * @example 1763453833
             */
            'arrived_at' => $this->resource['arrived_at'],

            /**
             * Passenger count
             *
             * @var int
             *
             * @example 2
             */
            'passenger_count' => $this->resource['passenger_count'],

            /**
             * Trip
             *
             * Trip accessibility data
             *
             * @var TripRequestDetailResource
             */
            'trip_accessibility' => AccessibilityRequirementsResource::collection($this->resource['trip_accessibility']),
        ];
    }
}
