<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Socket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Request Cancelled Broadcast Resource
 *
 * Formats cancellation data for socket broadcast to riders
 */
class TripRequestCancelledBroadcastResource extends JsonResource
{
    public function __construct(
        public readonly int $tripId,
        public readonly string $reason,
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
             * Cancellation reason
             *
             * @example "trip_assigned"
             *
             * @var string
             */
            'reason' => $this->reason,

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
