<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Enums\Trip\TripLocationTypeEnum;
use App\Http\Resources\Api\V1\Customer\Trip\AccessibilityRequirementsResource;
use App\Http\Resources\Api\V1\Customer\Trip\RiderInfoResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripPaymentResource;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Details Resource
 *
 * Formats detailed trip data including price breakdown, ride details, and accessibility
 */
class TripDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Trip $trip */
        $trip = $this->resource['trip'];
        $priceBreakdown = $this->resource['price_breakdown'];

        $originLocation = $trip->locations->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::ORIGIN)->first();
        $destinationLocation = $trip->locations->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)->first();

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
             * Trip status
             *
             * @var array{id: int, label: string}
             */
            'status' => [
                'id' => $trip->{Trip::COLUMN_STATUS}->value,
                'label' => $trip->{Trip::COLUMN_STATUS}->getLabel(),
            ],

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
             * Locations (from/to)
             *
             * @var array
             */
            'locations' => [
                'from' => [
                    'location' => $originLocation?->{TripLocation::COLUMN_LOCATION_TITLE} ?? '',
                    'sub_location' => $originLocation?->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                    'lat' => $originLocation ? (float) $originLocation->{TripLocation::COLUMN_LATITUDE} : 0,
                    'lng' => $originLocation ? (float) $originLocation->{TripLocation::COLUMN_LONGITUDE} : 0,
                ],
                'to' => [
                    'location' => $destinationLocation?->{TripLocation::COLUMN_LOCATION_TITLE} ?? '',
                    'sub_location' => $destinationLocation?->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                    'lat' => $destinationLocation ? (float) $destinationLocation->{TripLocation::COLUMN_LATITUDE} : 0,
                    'lng' => $destinationLocation ? (float) $destinationLocation->{TripLocation::COLUMN_LONGITUDE} : 0,
                ],
            ],

            /**
             * Price Breakdown
             *
             * @var TripPaymentResource[]
             */
            'price_breakdown' => TripPaymentResource::collection($priceBreakdown),

            /**
             * Ride details
             *
             * @var array
             */
            'ride_details' => [
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
                'waiting_time' => $trip->{Trip::COLUMN_WAITING_TIME},
            ],

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
