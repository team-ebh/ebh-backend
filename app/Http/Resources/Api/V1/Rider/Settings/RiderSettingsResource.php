<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rider Settings Resource
 *
 * @property int $trip_request_timeout_seconds
 * @property int $rider_location_update_interval_seconds_online
 * @property int $rider_location_update_interval_seconds_busy
 * @property int $arriving_at_poll_interval_seconds
 */
class RiderSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip request timeout in seconds
             *
             * @var int
             *
             * @example 240
             */
            'trip_request_timeout_seconds' => $this->resource['trip_request_timeout_seconds'],

            /**
             * Rider location update interval in seconds when online (idle)
             *
             * How often online (idle) riders should send their location updates to the server
             *
             * @var int
             *
             * @example 30
             */
            'rider_location_update_interval_seconds_online' => $this->resource['rider_location_update_interval_seconds_online'],

            /**
             * Rider location update interval in seconds when busy (on trip)
             *
             * How often busy riders should send their location updates to the server (faster interval)
             *
             * @var int
             *
             * @example 10
             */
            'rider_location_update_interval_seconds_busy' => $this->resource['rider_location_update_interval_seconds_busy'],

            /**
             * Arriving at poll interval in seconds
             *
             * How often to poll the server for "arriving at" time updates
             *
             * @var int
             *
             * @example 10
             */
            'arriving_at_poll_interval_seconds' => $this->resource['arriving_at_poll_interval_seconds'],
        ];
    }
}
