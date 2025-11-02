<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;

class GetTripFormDataAction
{
    /**
     * Get trip form data including all available enums
     */
    public function __invoke(): array
    {
        return [
            'trip_types' => TripTypeEnum::cases(),
            'vehicle_types' => TripVehicleTypeEnum::cases(),
            'accessibility_requirements' => AccessibilityRequirementsEnum::cases(),
            'maximum_passengers' => 6,
        ];
    }
}
