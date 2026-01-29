<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vehicle Snapshot Resource
 *
 * Formats vehicle snapshot data stored in trips table
 */
class VehicleSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Car type name
             *
             * @example "Sedan"
             *
             * @var string|null
             */
            'car_type' => $this->resource['car_type'] ?? null,

            /**
             * Car color name
             *
             * @example "Black"
             *
             * @var string|null
             */
            'car_color' => $this->resource['car_color'] ?? null,

            /**
             * Car make name
             *
             * @example "Toyota"
             *
             * @var string|null
             */
            'car_make' => $this->resource['car_make'] ?? null,

            /**
             * Car model name
             *
             * @example "Camry"
             *
             * @var string|null
             */
            'car_model' => $this->resource['car_model'] ?? null,

            /**
             * Full model description (make + model)
             *
             * @example "Toyota Camry"
             *
             * @var string|null
             */
            'model' => $this->resource['model'] ?? null,

            /**
             * Vehicle year
             *
             * @example 2023
             *
             * @var int|null
             */
            'year' => $this->resource['year'] ?? null,

            /**
             * Vehicle plate number
             *
             * @example "ABC123"
             *
             * @var string|null
             */
            'plate_number' => $this->resource['plate_number'] ?? null,

            /**
             * Passenger capacity
             *
             * @example 4
             *
             * @var int|null
             */
            'passenger_capacity' => $this->resource['passenger_capacity'] ?? null,
        ];
    }
}
