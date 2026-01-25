<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Http\Resources\Api\V1\Customer\Trip\TripTypeResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Upcoming Trip List Resource
 *
 * Formats trip data for upcoming trips list (scheduled trips waiting for processing)
 */
class UpcomingTripListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Trip $trip */
        $trip = $this->resource;

        return [
            /**
             * Trip identifier
             *
             * @example 12
             *
             * @var int
             */
            'id' => $trip->{Trip::COLUMN_ID},

            /**
             * Trip locations sorted by sequence
             *
             * @var HistoryTripLocationResource[]
             */
            'locations' => HistoryTripLocationResource::collection($this->whenLoaded('locations')),

            /**
             * Scheduled time (Unix timestamp)
             *
             * @example 1730556234
             *
             * @var int
             */
            'schedule_date_time' => $trip->{Trip::COLUMN_SCHEDULED_TIME}?->timestamp,

            /**
             * Trip type information
             *
             * @var TripTypeResource
             */
            'type' => new TripTypeResource($trip->{Trip::COLUMN_TRIP_TYPE_ID}),
        ];
    }
}
