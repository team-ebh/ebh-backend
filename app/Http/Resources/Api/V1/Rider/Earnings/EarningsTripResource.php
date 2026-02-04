<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Earnings;

use App\Http\Resources\Api\V1\Rider\Trip\History\HistoryTripLocationResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Earnings Trip Resource
 *
 * Formats trip data for earnings trips list
 */
class EarningsTripResource extends JsonResource
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
            'locations' => HistoryTripLocationResource::collection($trip->locations),

            /**
             * Trip creation date and time (timestamp)
             *
             * @example 1705932800
             *
             * @var int
             */
            'date_time' => $trip->{Trip::COLUMN_CREATED_AT}->timestamp,

            /**
             * Trip earnings (total price minus commission)
             *
             * @example 8.500
             *
             * @var float
             */
            'price' => $trip->getRiderEarnings(),

            /**
             * Currency code
             *
             * @example "KWD"
             *
             * @var string
             */
            'currency' => $trip->{Trip::COLUMN_CURRENCY}?->getLabel(),
        ];
    }
}
