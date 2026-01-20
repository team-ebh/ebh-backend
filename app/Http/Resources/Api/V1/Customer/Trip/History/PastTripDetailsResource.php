<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\Concerns\FormatsRiderData;
use App\Http\Resources\Api\V1\Customer\Trip\RiderInfoResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripPaymentResource;
use App\Models\Trip;
use App\Models\Vehicle;
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
    use FormatsRiderData;

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
             * Rider information (null if no rider was assigned)
             *
             * @var RiderInfoResource|null
             */
            'rider' => $this->when(
                $trip->rider !== null,
                fn () => new RiderInfoResource($this->formatRiderData($trip))
            ),

            /**
             * Vehicle plate number (null if no rider was assigned)
             *
             * @example "ABC-123"
             *
             * @var string|null
             */
            'vehicle_plate_number' => $this->when(
                $trip->rider?->vehicle !== null,
                fn () => $trip->rider->vehicle->{Vehicle::COLUMN_PLATE_NUMBER}
            ),

            /**
             * Trip type information
             *
             * @var array{id: int, label: string}
             */
            'trip_type' => [
                'id' => $trip->{Trip::COLUMN_TRIP_TYPE_ID}->value,
                'label' => $trip->{Trip::COLUMN_TRIP_TYPE_ID}->getLabel(),
            ],

            /**
             * Trip locations sorted by sequence
             *
             * @var HistoryTripLocationResource[]
             */
            'locations' => HistoryTripLocationResource::collection(
                $trip->locations->sortBy('sequence')->values()
            ),

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
             * @var array{id: int, label: string, description: string, icon: string}
             */
            'ride_type' => [
                'id' => $trip->{Trip::COLUMN_RIDE_TYPE}->value,
                'label' => $trip->{Trip::COLUMN_RIDE_TYPE}->getLabel(),
                'description' => $trip->{Trip::COLUMN_RIDE_TYPE}->getDescription(),
                'icon' => $trip->{Trip::COLUMN_RIDE_TYPE}->getIcon(),
            ],

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
             * @example 30
             *
             * @var int|null
             */
            'waiting_time' => $this->when(
                $trip->{Trip::COLUMN_WAITING_TIME} !== null,
                fn () => $trip->{Trip::COLUMN_WAITING_TIME}
            ),
        ];
    }
}
