<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\RideTypeHistoryResource;
use App\Http\Resources\Api\V1\Customer\Trip\RideTypeResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripPaymentResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Past Trip Details Resource
 *
 * Formats detailed trip data for past (completed/cancelled) trips
 * Includes customer information
 */
class PastTripDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Trip $trip */
        $trip = $this->resource['trip'];
        $priceBreakdown = $this->resource['price_breakdown'];

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
             * Trip status
             *
             * @var StatusResource
             */
            'status' => new StatusResource($trip->{Trip::COLUMN_STATUS}),

            /**
             * Trip locations sorted by sequence
             *
             * @var HistoryTripLocationResource[]
             */
            'locations' => HistoryTripLocationResource::collection($trip->locations),

            /**
             * Accessibility requirements selected for this trip
             *
             * @var AccessibilityRequirementsResource[]|null
             */
            'accessibility' => $this->when(
                $trip->accessibility->isNotEmpty(),
                fn () => AccessibilityRequirementsResource::collection($trip->accessibility)
            ),

            /**
             * Price Breakdown
             *
             * @var TripPaymentResource[]
             */
            'price_breakdown' => TripPaymentResource::collection($priceBreakdown),

            /**
             * Ride type information
             *
             * @var RideTypeHistoryResource
             */
            'ride_type' => new RideTypeResource($trip->{Trip::COLUMN_RIDE_TYPE}),

            /**
             * Number of passengers
             *
             * @example 2
             *
             * @var int
             */
            'passenger_count' => $trip->{Trip::COLUMN_PASSENGER_COUNT},

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
