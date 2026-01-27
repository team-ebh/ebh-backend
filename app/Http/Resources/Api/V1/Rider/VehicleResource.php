<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider;

use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vehicle Resource
 *
 * Formats vehicle information for rider profile response
 */
class VehicleResource extends JsonResource
{
    /**
     * @var Vehicle
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            /**
             * Vehicle ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $this->resource->{Vehicle::COLUMN_ID},

            /**
             * Car type name
             *
             * @example "Sedan"
             *
             * @var string|null
             */
            'car_type' => $this->resource->carType?->translated(VehicleSetting::COLUMN_NAME),

            /**
             * Car color name
             *
             * @example "Black"
             *
             * @var string|null
             */
            'car_color' => $this->resource->carColor?->translated(VehicleSetting::COLUMN_NAME),

            /**
             * Passenger capacity
             *
             * @example 4
             *
             * @var int|null
             */
            'passenger_capacity' => $this->resource->passengerCapacity?->{VehicleSetting::COLUMN_CAPACITY},

            /**
             * Car make name
             *
             * @example "Toyota"
             *
             * @var string|null
             */
            'car_make' => $this->resource->carMake?->translated(VehicleSetting::COLUMN_NAME),

            /**
             * Car model name
             *
             * @example "Camry"
             *
             * @var string|null
             */
            'car_model' => $this->resource->carModel?->translated(VehicleSetting::COLUMN_NAME),

            /**
             * Full model description (make + model)
             *
             * @example "Toyota Camry"
             *
             * @var string
             */
            'model' => $this->resource->getModelCar(),

            /**
             * Vehicle year
             *
             * @example 2023
             *
             * @var int|null
             */
            'year' => $this->resource->{Vehicle::COLUMN_YEAR} !== null
                ? (int) $this->resource->{Vehicle::COLUMN_YEAR}
                : null,

            /**
             * Vehicle plate number
             *
             * @example "ABC123"
             *
             * @var string|null
             */
            'plate_number' => $this->resource->{Vehicle::COLUMN_PLATE_NUMBER},

            /**
             * Vehicle accessibility features
             *
             * @var array<AccessibilityFeatureResource>
             */
            'accessibility_features' => AccessibilityFeatureResource::collection(
                $this->resource->accessibilityFeatures
            ),
        ];
    }
}
