<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripPaymentResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripTypeResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Upcoming Trip Details Resource
 *
 * Formats detailed trip data for upcoming (scheduled) trips
 */
class UpcomingTripDetailsResource extends JsonResource
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
            'trip_type' => new TripTypeResource($trip->{Trip::COLUMN_TRIP_TYPE_ID}),

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
             * Total Price
             *
             * @var TripPaymentResource
             */
            'total_price' => new TripPaymentResource($this->resource['total_price']),

            /**
             * Ride type information
             *
             * @var RideTypeHistoryResource
             */
            'type' => new RideTypeHistoryResource($trip->{Trip::COLUMN_RIDE_TYPE}),

            /**
             * Number of passengers
             *
             * @example 2
             *
             * @var int
             */
            'passenger_count' => $trip->{Trip::COLUMN_PASSENGER_COUNT},
        ];
    }
}
