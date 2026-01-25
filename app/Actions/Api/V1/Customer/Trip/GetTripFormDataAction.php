<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\VehicleSetting;

class GetTripFormDataAction
{
    private const int DEFAULT_MAX_PASSENGERS = 6;

    /**
     * Get trip form data including all available enums
     */
    public function __invoke(): array
    {
        return [
            'trip_types' => TripTypeEnum::cases(),
            'vehicle_types' => TripVehicleTypeEnum::cases(),
            'accessibility_requirements' => AccessibilityRequirementsEnum::cases(),
            'maximum_passengers' => $this->getMaximumPassengers(),
        ];
    }

    private function getMaximumPassengers(): int
    {
        $maxCapacity = VehicleSetting::query()
            ->where(VehicleSetting::COLUMN_TYPE, VehicleSetting::TYPE_PASSENGER_CAPACITY)
            ->max(VehicleSetting::COLUMN_CAPACITY);

        return (int) ($maxCapacity ?? self::DEFAULT_MAX_PASSENGERS);
    }
}
