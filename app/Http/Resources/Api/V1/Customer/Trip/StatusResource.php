<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Trip\TripStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var TripStatusEnum $tripStatus
         */
        $tripStatus = $this->resource;

        return [
            /**
             * Trip Status identifier
             *
             * @example 1
             *
             * @var int
             */
            'id' => $tripStatus->value,

            /**
             * Trip status label (translated based on request language)
             *
             * @example "Completed"
             *
             * @var string
             */
            'label' => $tripStatus->getLabel(),
        ];
    }
}
