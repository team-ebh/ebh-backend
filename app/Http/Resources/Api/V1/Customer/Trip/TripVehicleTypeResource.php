<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\TripVehicleTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripVehicleTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var TripVehicleTypeEnum $vehicleType
         */
        $vehicleType = $this->resource;

        return [
            /**
             * Vehicle type identifier
             *
             * @example 1
             *
             * @var int
             */
            'id' => $vehicleType->value,

            /**
             * Vehicle type label (translated based on request language)
             *
             * @example "Wheelchair Accessible"
             *
             * @var string
             */
            'label' => $vehicleType->getLabel(),

            /**
             * Vehicle type description (translated based on request language)
             *
             * @example "Standard Wheelchair transport"
             *
             * @var string
             */
            'description' => $vehicleType->getDescription(),
        ];
    }
}
