<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Models\TripLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * History Trip Location Resource
 *
 * Formats location data for trip history list
 */
class HistoryTripLocationResource extends JsonResource
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
             * Location type
             *
             * @var array{id: int, label: string}
             */
            'type' => [
                'id' => $location->{TripLocation::COLUMN_TYPE}->value,
                'label' => $location->{TripLocation::COLUMN_TYPE}->getLabel(),
            ],

            /**
             * Location sequence order
             *
             * @example 1
             *
             * @var int
             */
            'sequence' => $location->{TripLocation::COLUMN_SEQUENCE},
        ];
    }
}
