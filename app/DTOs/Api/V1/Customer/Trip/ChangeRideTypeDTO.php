<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Http\Request;

/**
 * Change Ride Type DTO
 *
 * Data Transfer Object for ride type change and pricing calculation
 */
class ChangeRideTypeDTO implements RequestDataTransferObject
{
    public ?int $customerId;

    public Trip $trip;

    public ?float $originLatitude;

    public ?float $originLongitude;

    public ?float $destinationLatitude;

    public ?float $destinationLongitude;

    public RideTypeEnum $rideTypeId;

    public ?int $waitingTimeMinutes;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('api')->id();
        $this->trip = $request->route()->parameter('trip');

        // Load locations to get coordinates
        $this->trip->load('locations');

        $originLocation = $this->trip->locations->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::ORIGIN)->first();
        $destinationLocation = $this->trip->locations->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)->first();

        // Get origin from trip locations
        $this->originLatitude = $originLocation ? (float) $originLocation->{TripLocation::COLUMN_LATITUDE} : 0;
        $this->originLongitude = $originLocation ? (float) $originLocation->{TripLocation::COLUMN_LONGITUDE} : 0;

        // Get destination from request if provided, otherwise use trip's destination
        $this->destinationLatitude = $request->filled('destination_latitude')
            ? (float) $request->post('destination_latitude')
            : ($destinationLocation ? (float) $destinationLocation->{TripLocation::COLUMN_LATITUDE} : 0);
        $this->destinationLongitude = $request->filled('destination_longitude')
            ? (float) $request->post('destination_longitude')
            : ($destinationLocation ? (float) $destinationLocation->{TripLocation::COLUMN_LONGITUDE} : 0);

        $this->rideTypeId = $request->enum('ride_type_id', RideTypeEnum::class);
        $this->waitingTimeMinutes = $request->filled('waiting_time_minutes') ? (int) $request->post('waiting_time_minutes') : null;
    }
}
