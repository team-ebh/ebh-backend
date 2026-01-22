<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Enums\Trip\RideTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ride Type Resource for History Trips
 *
 * Simple resource with only id and label
 */
class RideTypeHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RideTypeEnum $rideType */
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
        ];
    }
}
