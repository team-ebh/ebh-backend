<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Enums\Trip\TripHistoryTypeEnum;
use App\Http\Resources\Api\V1\Customer\Trip\RiderInfoResource;
use App\Models\Rider;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip History Resource
 *
 * Formats trip history data for upcoming and past trips
 */
class TripHistoryResource extends JsonResource
{
    public function __construct(
        Trip $resource,
        private readonly TripHistoryTypeEnum $historyType
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var Trip $trip */
        $trip = $this->resource;

        $locations = $trip->locations->sortBy('sequence');

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
             * Label - Trip type for upcoming, Status (simple) for past
             *
             * @var array{id: int, label: string}
             */
            'label' => $this->getLabel($trip),

            /**
             * Rider information (null if no rider assigned)
             *
             * @var RiderInfoResource|null
             */
            'rider' => $this->when(
                $trip->rider !== null,
                fn () => new RiderInfoResource($this->formatRiderData($trip))
            ),

            /**
             * Trip locations sorted by sequence
             *
             * @var HistoryTripLocationResource[]
             */
            'locations' => HistoryTripLocationResource::collection($locations),

            /**
             * Scheduled time (Unix timestamp, only for scheduled trips)
             *
             * @example 1730556234
             *
             * @var int|null
             */
            'scheduled_time' => $trip->{Trip::COLUMN_SCHEDULED_TIME}?->timestamp,

            /**
             * Trip creation time (Unix timestamp)
             *
             * @example 1730556234
             *
             * @var int
             */
            'created_at' => $trip->created_at->timestamp,
        ];
    }

    /**
     * Get label based on history type
     * - Upcoming: shows trip type (Scheduled)
     * - Past: shows simple status (Completed, Cancelled)
     */
    private function getLabel(Trip $trip): array
    {
        if ($this->historyType === TripHistoryTypeEnum::UPCOMING) {
            return [
                'id' => $trip->{Trip::COLUMN_TRIP_TYPE_ID}->value,
                'label' => $trip->{Trip::COLUMN_TRIP_TYPE_ID}->getLabel(),
            ];
        }

        // For past trips, show simple status
        return [
            'id' => $trip->{Trip::COLUMN_STATUS}->value,
            'label' => $trip->{Trip::COLUMN_STATUS}->getSimpleLabel(),
        ];
    }

    /**
     * Format rider data for RiderInfoResource
     */
    private function formatRiderData(Trip $trip): array
    {
        $rider = $trip->rider;

        return [
            'id' => $rider->id,
            'image' => $rider->getFirstMediaLink(),
            'name' => $rider->{Rider::COLUMN_FULL_NAME},
            'phone_number' => $rider->{Rider::COLUMN_PHONE_NUMBER},
            'rating' => (float) ($rider->rating ?? 0),
            'accessibility_certifications' => $rider->accessibilityCertifications ?? collect(),
        ];
    }
}
