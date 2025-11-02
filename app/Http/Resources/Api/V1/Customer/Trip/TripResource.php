<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

        // Get origin and destination locations from trip_locations table
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
            'id' => $trip->id,

            /**
             * Origin location details
             *
             * @var TripLocationResource
             */
            'from' => new TripLocationResource(
                location: $originLocation?->{TripLocation::COLUMN_LOCATION_TITLE} ?? '',
                subLocation: $originLocation?->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                latitude: $originLocation ? (float) $originLocation->{TripLocation::COLUMN_LATITUDE} : 0,
                longitude: $originLocation ? (float) $originLocation->{TripLocation::COLUMN_LONGITUDE} : 0,
            ),

            /**
             * Destination location details
             *
             * @var TripLocationResource
             */
            'to' => new TripLocationResource(
                location: $destinationLocation?->{TripLocation::COLUMN_LOCATION_TITLE} ?? '',
                subLocation: $destinationLocation?->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
                latitude: $destinationLocation ? (float) $destinationLocation->{TripLocation::COLUMN_LATITUDE} : 0,
                longitude: $destinationLocation ? (float) $destinationLocation->{TripLocation::COLUMN_LONGITUDE} : 0,
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
             * @var AnonymousResourceCollection<RideTypeResource>
             */
            'ride_types' => RideTypeResource::collection(RideTypeEnum::cases()),

            /**
             * Price Breakdown
             *
             * @var AnonymousResourceCollection<TripPaymentResource>
             */
            'price_breakdown' => TripPaymentResource::collection($this->resource['price_breakdown']),

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
