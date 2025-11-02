<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\TripTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var TripTypeEnum $tripType
         */
        $tripType = $this->resource;

        return [
            /**
             * Trip type identifier
             *
             * @example 1
             *
             * @var int
             */
            'id' => $tripType->value,

            /**
             * Trip type label (translated based on request language)
             *
             * @example "Ride Now"
             *
             * @var string
             */
            'label' => $tripType->getLabel(),
        ];
    }
}
