<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Trip Store DTO
 *
 * Data Transfer Object for trip creation
 */
class TripStoreDTO implements RequestDataTransferObject
{
    public ?int $customerId;

    public ?string $originLocationTitle;

    public ?string $originLocationSubTitle;

    public float $originLatitude;

    public float $originLongitude;

    public ?string $destinationLocationTitle;

    public ?string $destinationLocationSubTitle;

    public float $destinationLatitude;

    public float $destinationLongitude;

    public int $tripTypeId;

    public int $vehicleTypeId;

    public array $accessibilityRequirements;

    public int $passengerCount;

    public ?Carbon $scheduledDateTime;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();

        // Origin location
        $this->originLocationTitle = $request->post('origin_location_title');
        $this->originLocationSubTitle = $request->post('origin_location_sub_title');
        $this->originLatitude = (float) $request->post('origin_latitude');
        $this->originLongitude = (float) $request->post('origin_longitude');

        // Destination location
        $this->destinationLocationTitle = $request->post('destination_location_title');
        $this->destinationLocationSubTitle = $request->post('destination_location_sub_title');
        $this->destinationLatitude = (float) $request->post('destination_latitude');
        $this->destinationLongitude = (float) $request->post('destination_longitude');

        // Trip details
        $this->tripTypeId = (int) $request->post('trip_type_id');
        $this->vehicleTypeId = (int) $request->post('vehicle_type_id');

        // Convert accessibility requirements to enum instances
        $accessibilityIds = $request->post('accessibility_requirements', []);
        $this->accessibilityRequirements = array_map(
            fn ($id) => AccessibilityRequirementsEnum::from((int) $id),
            $accessibilityIds
        );

        $this->passengerCount = (int) $request->post('passenger_count');

        // Schedule date time (for scheduled trips)
        $scheduleDateTime = $request->post('schedule_date_time');
        $this->scheduledDateTime = is_null($scheduleDateTime) ? null : Carbon::createFromTimestamp((int) $scheduleDateTime);
    }
}
