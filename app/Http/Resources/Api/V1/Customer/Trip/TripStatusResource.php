<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Http\Resources\Api\V1\Customer\StatusResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Status Resource
 *
 * Formats comprehensive trip status response including rider, vehicle, and location data
 */
class TripStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Found
             *
             * Whether a taxi/rider has been found
             *
             * @example true
             *
             * @var bool
             */
            'found' => $this->resource['found'],

            /**
             * Status
             *
             * Current trip status
             *
             * @var StatusResource
             */
            'status' => new StatusResource($this->resource['status']),

            /**
             * Arrived Time
             *
             * Timestamp when driver arrived at pickup location (null if not arrived yet)
             *
             * @example 1730556234
             *
             * @var int|null
             */
            'arrived_time' => $this->resource['arrived_time'],

            /**
             * Rider Information
             *
             * Rider/driver details (only when found = true)
             *
             * @var RiderInfoResource|null
             */
            'rider' => $this->when(
                $this->resource['rider'] !== null,
                fn () => new RiderInfoResource($this->resource['rider'])
            ),

            /**
             * Vehicle Information
             *
             * Vehicle details (only when found = true)
             *
             * @var VehicleInfoResource|null
             */
            'vehicle' => $this->when(
                $this->resource['vehicle'] !== null,
                fn () => new VehicleInfoResource($this->resource['vehicle'])
            ),

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
        ];
    }
}
