<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip;

use App\Http\Resources\Api\PriceResource;
use App\Http\Resources\Api\V1\Customer\Trip\FormattedLocationResource;
use App\Http\Resources\Api\V1\Customer\Trip\MapLocationResource;
use App\Http\Resources\Api\V1\Customer\Trip\RideTypeResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripTypeResource;
use App\Http\Resources\Api\V1\Rider\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Accept Trip Request Resource
 *
 * Formats accepted trip request data for rider API responses
 *
 * @property array $resource
 */
class AcceptTripRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip
             *
             * Complete trip object
             *
             * @var TripRequestDetailResource
             */
            'trip_request' => new TripRequestDetailResource($this->resource['trip_request']),

            /**
             * Map Locations
             *
             * Simplified location coordinates for map display (lat, lng, type, sequence only)
             *
             * @var MapLocationResource[]
             */
            'map_locations' => MapLocationResource::collection($this->resource['map_locations']),

            /**
             * Formatted Locations
             *
             * Location data structured as from/to pairs with full details
             *
             * @var FormattedLocationResource[]
             */
            'formatted_locations' => FormattedLocationResource::collection($this->resource['formatted_locations']),

            /**
             * Payment Information
             *
             * Trip payment with price and currency
             *
             * @var PriceResource
             */
            'payment' => $this->resource['payment'],

            /**
             * Trip Action
             *
             * Contains next action information and trip completion status
             *
             * @var TripActionResource
             */
            'trip_action' => new TripActionResource([
                'next_action' => $this->resource['next_action'] ?? null,
                'trip_completed' => $this->resource['trip_completed'] ?? false,
            ]),

            /**
             * Customer Information
             *
             * Contains customer details (full name, image, phone)
             *
             * @var CustomerResource
             */
            'customer' => new CustomerResource($this->resource['customer']),

            /**
             * Trip Type
             *
             * Type of the trip (ride now, scheduled, etc.)
             *
             * @var TripTypeResource
             */
            'trip_type' => new TripTypeResource($this->resource['trip_type_id']),

            /**
             * Trip Ride Type
             *
             * Ride type of the trip (one way, round trip, etc.)
             *
             * @var RideTypeResource
             */
            'trip_ride_type' => new RideTypeResource($this->resource['ride_type']),
        ];
    }
}
