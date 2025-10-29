<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\RideTypeEnum;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Resource
 *
 * Formats trip data for API responses
 */
class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Trip $trip
         */
        $trip = $this->resource['trip'];

        return [
            /**
             * Trip identifier
             *
             * @example 12
             *
             * @var int
             */
            'id' => $trip->id,

            /**
             * Origin location details
             *
             * @var TripLocationResource
             */
            'from' => new TripLocationResource(
                location: $trip->{Trip::COLUMN_ORIGIN_LOCATION},
                subLocation: $trip->{Trip::COLUMN_ORIGIN_SUB_LOCATION},
                latitude: (float) $trip->{Trip::COLUMN_ORIGIN_LATITUDE},
                longitude: (float) $trip->{Trip::COLUMN_ORIGIN_LONGITUDE},
            ),

            /**
             * Destination location details
             *
             * @var TripLocationResource
             */
            'to' => new TripLocationResource(
                location: $trip->{Trip::COLUMN_DESTINATION_LOCATION},
                subLocation: $trip->{Trip::COLUMN_DESTINATION_SUB_LOCATION},
                latitude: (float) $trip->{Trip::COLUMN_DESTINATION_LATITUDE},
                longitude: (float) $trip->{Trip::COLUMN_DESTINATION_LONGITUDE},
            ),

            /**
             * Accessibility requirements
             *
             * @var AccessibilityRequirementsResource|null
             */
            'accessibility' => $this->when(
                count($this->resource['dto']->accessibilityRequirements),
                fn () => AccessibilityRequirementsResource::collection($trip->accessibility)
            ),

            /**
             * Ride types information
             *
             * @var RideTypeResource
             */
            'ride_types' => RideTypeResource::collection(RideTypeEnum::cases()),

            /**
             * Price Breakdown
             *
             * @var TripPaymentResource
             */
            'price_breakdown' => $this->when(
                count($this->resource['price_breakdown']),
                fn () => TripPaymentResource::collection($this->resource['price_breakdown'])
            ),

            /**
             * Price Estimation
             *
             * @var TripPaymentResource
             */
            'price_estimation' => $this->when(
                count($this->resource['price_estimation']),
                fn () => new TripPaymentResource($this->resource['price_estimation'])
            ),
        ];
    }
}
