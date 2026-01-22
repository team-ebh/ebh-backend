<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\Traits\HasVehicleInformation;
use App\Http\Resources\Api\V1\Customer\Trip\TripPaymentResource;
use App\Http\Resources\Api\V1\Customer\Trip\VehicleInfoResource;
use App\Models\Payment;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Past Trip Details Resource
 *
 * Formats detailed trip data for past (completed/cancelled) trips
 * Includes rider information and vehicle plate number
 */
class PastTripDetailsResource extends JsonResource
{
    use HasVehicleInformation;

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
             * Rider information (null if trip was cancelled before rider accepted)
             *
             * @var RiderInfoHistoryTripResource|null
             */
            'rider' => $this->when(
                $trip->rider !== null,
                fn () => new RiderInfoHistoryTripResource($trip->rider)
            ),

            /**
             * Vehicle Information
             *
             * Vehicle details (null if trip was cancelled before rider accepted)
             *
             * @var VehicleInfoResource|null
             */
            'vehicle' => $this->when(
                $this->getVehicleInformation($trip) !== null,
                fn () => new VehicleInfoResource($this->getVehicleInformation($trip))
            ),

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
            'ride_type' => new RideTypeHistoryResource($trip->{Trip::COLUMN_RIDE_TYPE}),

            /**
             * Number of passengers
             *
             * @example 2
             *
             * @var int
             */
            'passenger_count' => $trip->{Trip::COLUMN_PASSENGER_COUNT},

            /**
             * Waiting time in minutes (only for ROUND_TRIP_WAIT, null otherwise)
             *
             * @example "30 min"
             *
             * @var string|null
             */
            'waiting_time' => $this->when(
                ! is_null($trip->{Trip::COLUMN_WAITING_TIME}),
                fn () => $trip->{Trip::COLUMN_WAITING_TIME} . ' ' . trans('trips.admin.timeline.minutes')
            ),

            /**
             * Payment number (if trip has a paid payment)
             *
             * If this field exists, show download receipt button
             *
             * @example "PAY-123456789"
             *
             * @var string|null
             */
            'payment_number' => $this->when(
                ! is_null($trip->order?->paidPayment),
                fn () => $trip->order->paidPayment->{Payment::COLUMN_PAYMENT_NUMBER}
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
