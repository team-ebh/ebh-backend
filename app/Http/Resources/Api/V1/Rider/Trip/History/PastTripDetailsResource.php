<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Http\Resources\Api\V1\Customer\Trip\RideTypeResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripPaymentResource;
use App\Http\Resources\Api\V1\Shared\VehicleSnapshotResource;
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
             * @var HistoryTripLocationDetailsResource[]
             */
            'locations' => HistoryTripLocationDetailsResource::collection($trip->locations),

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
             * @var RideTypeResource
             */
            'ride_type' => new RideTypeResource($trip->{Trip::COLUMN_RIDE_TYPE}),

            /**
             * Trip duration in minutes
             *
             * @example 25
             *
             * @var int|null
             */
            'duration' => $trip->{Trip::COLUMN_DURATION_MINUTES},

            /**
             * Trip distance in meters
             *
             * @example 5000
             *
             * @var int|null
             */
            'distance' => $trip->{Trip::COLUMN_DISTANCE_METERS},

            /**
             * Payment information (only for completed trips with non-cash payment)
             *
             * @var PaymentInfoResource|null
             */
            'payment' => $this->when(
                $trip->isCompleted() && $trip->order !== null && ! $trip->isCashPayment(),
                fn () => new PaymentInfoResource($trip->order)
            ),

            /**
             * Vehicle Information (snapshot from trip acceptance)
             *
             * @var VehicleSnapshotResource|null
             */
            'vehicle' => $this->when(
                $trip->{Trip::COLUMN_VEHICLE_SNAPSHOT} !== null,
                fn () => new VehicleSnapshotResource($trip->{Trip::COLUMN_VEHICLE_SNAPSHOT})
            ),

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
