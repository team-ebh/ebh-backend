<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Http\Resources\Api\V1\Customer\Trip\VehicleInfoResource;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Past Trip List Resource
 *
 * Formats trip data for past trips list (completed and canceled trips)
 */
class PastTripListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Trip $trip */
        $trip = $this->resource;

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
             * Rider information
             *
             * @var RiderInfoHistoryTripResource
             */
            'rider' => new RiderInfoHistoryTripResource($trip->rider),

            /**
             * Vehicle Information
             *
             * Vehicle details (only when found = true)
             *
             * @var VehicleInfoResource|null
             */
            'vehicle' => new VehicleInfoResource($this->getVehicleInformation($trip)),

            /**
             * Trip locations sorted by sequence
             *
             * @var HistoryTripLocationResource[]
             */
            'locations' => HistoryTripLocationResource::collection($this->resource['locations']),

            /**
             * Trip status
             *
             * @var StatusResource
             */
            'status' => new StatusResource($trip->{Trip::COLUMN_STATUS}),
        ];
    }

    public function getVehicleInformation(Trip $trip): array
    {
        return [
            'model' => $trip->rider->vehicle->getModelCar(),
            'plate_number' => $trip->rider->vehicle->{Vehicle::COLUMN_PLATE_NUMBER},
        ];
    }
}
