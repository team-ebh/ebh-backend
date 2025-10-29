<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Trip\RideTypeEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
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

        // Get origin from trip model
        $this->originLatitude = (float) $this->trip->{Trip::COLUMN_ORIGIN_LATITUDE};
        $this->originLongitude = (float) $this->trip->{Trip::COLUMN_ORIGIN_LONGITUDE};

        // Get destination from request if provided, otherwise use trip's destination
        $this->destinationLatitude = $request->filled('destination_latitude')
            ? (float) $request->post('destination_latitude')
            : (float) $this->trip->{Trip::COLUMN_DESTINATION_LATITUDE};
        $this->destinationLongitude = $request->filled('destination_longitude')
            ? (float) $request->post('destination_longitude')
            : (float) $this->trip->{Trip::COLUMN_DESTINATION_LONGITUDE};

        $this->rideTypeId = $request->enum('ride_type_id', RideTypeEnum::class);
        $this->waitingTimeMinutes = $request->filled('waiting_time_minutes') ? (int) $request->post('waiting_time_minutes') : null;
    }
}
