<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History\Traits;

use App\Models\Trip;
use App\Models\Vehicle;

/**
 * Trait for extracting vehicle information from a trip
 *
 * Shared between PastTripDetailsResource and PastTripListResource
 */
trait HasVehicleInformation
{
    public function getVehicleInformation(Trip $trip): ?array
    {
        if (! $trip->rider?->vehicle) {
            return null;
        }

        return [
            'model' => $trip->rider->vehicle->getModelCar(),
            'plate_number' => $trip->rider->vehicle->{Vehicle::COLUMN_PLATE_NUMBER},
        ];
    }
}
