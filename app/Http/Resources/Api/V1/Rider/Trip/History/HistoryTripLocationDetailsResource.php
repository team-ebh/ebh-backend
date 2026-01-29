<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Models\TripLocation;
use App\Models\TripLocationStatusLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * History Trip Location Details Resource
 *
 * Formats location data for trip history details page (includes date_time)
 */
class HistoryTripLocationDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TripLocation $location */
        $location = $this->resource;

        return [
            /**
             * Location title
             *
             * @example "Kuwait City"
             *
             * @var string
             */
            'title' => $location->{TripLocation::COLUMN_LOCATION_TITLE} ?? '',

            /**
             * Location sub title
             *
             * @example "Block 5, Street 10"
             *
             * @var string|null
             */
            'sub_title' => $location->{TripLocation::COLUMN_LOCATION_SUB_TITLE},

            /**
             * Location date time (timestamp)
             * For origin: picked up time
             * For destination: dropped off or completed time
             *
             * @example 1705932800
             *
             * @var int|null
             */
            'date_time' => $this->getLocationDateTime($location),
        ];
    }

    /**
     * Get the appropriate date time for the location
     * - Origin: PICKED_UP status timestamp
     * - Destination: DROPPED_OFF or COMPLETED status timestamp
     */
    private function getLocationDateTime(TripLocation $location): ?int
    {
        if (! $location->relationLoaded('statusLogs') || $location->statusLogs->isEmpty()) {
            return null;
        }

        $statusLog = $location->isOrigin()
            ? $location->statusLogs->firstWhere(TripLocationStatusLog::COLUMN_STATUS, TripLocationStatusEnum::PICKED_UP)
            : $location->statusLogs->first(fn ($log) => in_array($log->{TripLocationStatusLog::COLUMN_STATUS}, [
                TripLocationStatusEnum::DROPPED_OFF,
                TripLocationStatusEnum::COMPLETED,
            ]));

        return $statusLog?->created_at?->timestamp;
    }
}
