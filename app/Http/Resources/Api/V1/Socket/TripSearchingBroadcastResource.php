<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Socket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Searching Broadcast Resource
 *
 * Formats search progress data for socket broadcast to customers
 */
class TripSearchingBroadcastResource extends JsonResource
{
    public function __construct(
        public readonly int $tripId,
        public readonly int $riderCount,
        public readonly int $radiusMeters,
        public readonly int $searchAttempt,
    ) {
        parent::__construct([]);
    }

    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip identifier
             *
             * @example 123
             *
             * @var int
             */
            'trip_id' => $this->tripId,

            /**
             * Number of riders who received the request
             *
             * @example 5
             *
             * @var int
             */
            'rider_count' => $this->riderCount,

            /**
             * Search radius in meters
             *
             * @example 200
             *
             * @var int
             */
            'search_radius_meters' => $this->radiusMeters,

            /**
             * Search attempt number
             *
             * @example 1
             *
             * @var int
             */
            'search_attempt' => $this->searchAttempt,

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
