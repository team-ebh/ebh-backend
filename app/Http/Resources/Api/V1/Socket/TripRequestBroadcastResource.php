<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Socket;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Request Broadcast Resource
 *
 * Formats trip data for socket broadcast when sending new trip requests to riders
 */
class TripRequestBroadcastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Trip $trip
         */
        $trip = $this->resource;

        return [
            /**
             * Trip identifier
             *
             * @example 123
             *
             * @var int
             */
            'trip_id' => $trip->id,

            /**
             * Customer identifier
             *
             * @example 45
             *
             * @var int
             */
            'customer_id' => $trip->customer_id,

            /**
             * Trip type ID
             *
             * @example 1
             *
             * @var int
             */
            'trip_type' => $trip->trip_type_id,

            /**
             * Vehicle type ID
             *
             * @example 1
             *
             * @var int
             */
            'vehicle_type' => $trip->vehicle_type_id,

            /**
             * Number of passengers
             *
             * @example 2
             *
             * @var int
             */
            'passenger_count' => $trip->passenger_count,

            /**
             * Event timestamp
             *
             * @example "2025-11-12T10:30:00Z"
             *
             * @var string
             */
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
