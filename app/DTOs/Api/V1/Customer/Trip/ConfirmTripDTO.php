<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Http\Request;

/**
 * Confirm Trip DTO
 *
 * Data Transfer Object for trip confirmation with payment method and ride type data.
 * For scheduled trips, ride_type_id defaults to ONE_WAY.
 */
class ConfirmTripDTO implements RequestDataTransferObject
{
    public int $customerId;

    public Trip $trip;

    public ?float $firstOriginLatitude = null;

    public ?float $firstOriginLongitude = null;

    public ?float $lastDestinationLatitude = null;

    public ?float $lastDestinationLongitude = null;

    public ?string $destinationLocationTitle = null;

    public ?string $destinationLocationSubTitle = null;

    public ?float $destinationLatitude = null;

    public ?float $destinationLongitude = null;

    public RideTypeEnum $rideTypeId;

    public ?int $scheduledTime = null;

    public PaymentMethodEnum $paymentMethod;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();
        $this->trip = $request->route('trip');
        $this->paymentMethod = $request->enum('payment_method', PaymentMethodEnum::class);

        // For scheduled trips, default to ONE_WAY if not provided
        if ($this->trip->isScheduledTripType()) {
            $this->rideTypeId = $request->enum('ride_type_id', RideTypeEnum::class) ?? RideTypeEnum::ONE_WAY;
        } else {
            $this->rideTypeId = $request->enum('ride_type_id', RideTypeEnum::class);
        }

        // Load locations to get coordinates
        $this->trip->load('locations');

        $firstOriginLocation = $this->trip->locations->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::ORIGIN)->first();
        $lastDestinationLocation = $this->trip->locations
            ->where(TripLocation::COLUMN_TYPE, TripLocationTypeEnum::DESTINATION)
            ->sortByDesc(TripLocation::COLUMN_SEQUENCE)
            ->first();

        // Get origin from trip locations
        if ($firstOriginLocation) {
            $this->firstOriginLatitude = (float) $firstOriginLocation->{TripLocation::COLUMN_LATITUDE};
            $this->firstOriginLongitude = (float) $firstOriginLocation->{TripLocation::COLUMN_LONGITUDE};
        }

        // Get last destination if exists
        if ($lastDestinationLocation) {
            $this->lastDestinationLatitude = (float) $lastDestinationLocation->{TripLocation::COLUMN_LATITUDE};
            $this->lastDestinationLongitude = (float) $lastDestinationLocation->{TripLocation::COLUMN_LONGITUDE};
        }

        if (RideTypeEnum::hasSecondDestination($this->rideTypeId)) {
            $this->destinationLocationTitle = $request->post('destination_location_title');
            $this->destinationLocationSubTitle = $request->post('destination_location_sub_title');

            $this->destinationLatitude = (float) $request->post('destination_latitude');
            $this->destinationLongitude = (float) $request->post('destination_longitude');
        }

        if (RideTypeEnum::hasScheduleTime($this->rideTypeId)) {
            $this->scheduledTime = (int) $request->post('return_time');
        }
    }
}
