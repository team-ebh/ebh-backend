<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Action Resource
 *
 * Formats trip action response data (arrived, pickup, complete, drop_passenger, next_pickup)
 *
 * @property array $resource
 */
class TripActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Next action
             *
             * Indicates what action the rider should take next.
             *
             * Possible values:
             * - `arrived`: Rider should call /arrived endpoint (at origin location)
             * - `pickup`: Rider should call /picked-up endpoint (first pickup at origin)
             * - `drop_passenger`: Rider should call /completed endpoint to drop passenger at first destination (ROUND_TRIP_WAIT only)
             * - `next_pickup`: Rider should call /picked-up endpoint to pick up passenger again after waiting (ROUND_TRIP_WAIT only)
             * - `complete`: Rider should call /completed endpoint to finish trip at final destination
             * - `null`: No more actions, trip is completed
             *
             * Flow for ONE_WAY trips:
             * arrived → pickup → complete → null
             *
             * Flow for ROUND_TRIP trips:
             * arrived → pickup → complete (dest 1) → complete (dest 2) → null
             *
             * Flow for ROUND_TRIP_WAIT trips:
             * arrived → pickup → drop_passenger → next_pickup → complete → null
             *
             * @var string|null
             *
             * @example "pickup"
             */
            'next_action' => $this->resource['next_action'] ?? null,

            /**
             * Trip completed flag
             *
             * Indicates if the entire trip has been completed.
             * When true, rider status changes to ONLINE and no more actions are needed.
             *
             * @var bool
             *
             * @example true
             */
            'trip_completed' => $this->resource['trip_completed'] ?? false,
        ];
    }
}
