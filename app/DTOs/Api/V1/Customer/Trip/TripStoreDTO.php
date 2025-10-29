<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

/**
 * Trip Store DTO
 *
 * Data Transfer Object for trip creation
 */
class TripStoreDTO implements RequestDataTransferObject
{
    public ?int $customerId;

    public float $originLatitude;

    public float $originLongitude;

    public float $destinationLatitude;

    public float $destinationLongitude;

    public int $tripTypeId;

    public int $vehicleTypeId;

    public array $accessibilityRequirements;

    public int $passengerCount;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('api')->id();
        $this->originLatitude = (float) $request->post('origin_latitude');
        $this->originLongitude = (float) $request->post('origin_longitude');
        $this->destinationLatitude = (float) $request->post('destination_latitude');
        $this->destinationLongitude = (float) $request->post('destination_longitude');
        $this->tripTypeId = (int) $request->post('trip_type_id');
        $this->vehicleTypeId = (int) $request->post('vehicle_type_id');

        // Convert accessibility requirements to enum instances
        $accessibilityIds = $request->post('accessibility_requirements', []);
        $this->accessibilityRequirements = array_map(
            fn ($id) => AccessibilityRequirementsEnum::from((int) $id),
            $accessibilityIds
        );

        $this->passengerCount = (int) $request->post('passenger_count');
    }
}
