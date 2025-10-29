<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\RideTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RideTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var RideTypeEnum $rideType
         */
        $rideType = $this->resource;

        return [
            /**
             * Ride type identifier
             *
             * @example 1
             *
             * @var int
             */
            'id' => $rideType->value,

            /**
             * Ride type label (translated based on request language)
             *
             * @example "One Way"
             *
             * @var string
             */
            'label' => $rideType->getLabel(),

            /**
             * Ride type description (translated based on request language)
             *
             * @example "One-way trip to destination"
             *
             * @var string
             */
            'description' => $this->when(isset($rideType->description), fn () => $rideType->getDescription()),

            /**
             * @example "http://api.ebhapp.com/images/trip/types/wheelchair.svg"
             *
             * @var string
             */
            'icon' => $rideType->getIcon(),

            /**
             * @example true
             *
             * @var bool
             */
            'id_default' => $rideType->isDefault(),
        ];
    }
}
