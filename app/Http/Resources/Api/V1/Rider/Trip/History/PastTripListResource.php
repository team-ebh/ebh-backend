<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Past Trip List Resource
 *
 * Formats trip data for past trips list (completed and canceled trips)
 */
class PastTripListResource extends JsonResource
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
             * Trip number
             *
             * @example TRP-123
             *
             * @var string
             */
            'trip_number' => tripNumberFormat($trip),

            /**
             * Trip locations sorted by sequence
             *
             * @var HistoryTripLocationResource[]
             */
            'locations' => HistoryTripLocationResource::collection($trip->locations),

            /**
             * Trip status
             *
             * @var StatusResource
             */
            'status' => new StatusResource($trip->{Trip::COLUMN_STATUS}),

            /**
             * Trip creation date and time (timestamp)
             *
             * @example 1705932800
             *
             * @var int
             */
            'date_time' => $trip->{Trip::COLUMN_CREATED_AT}->timestamp,
        ];
    }
}
