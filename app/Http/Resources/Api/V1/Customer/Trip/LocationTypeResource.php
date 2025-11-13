<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Location Type Resource
 *
 * Formats location type information
 */
class LocationTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Type ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $this->resource->value,

            /**
             * Type Label
             *
             * @example "Origin"
             *
             * @var string
             */
            'label' => $this->resource->getLabel(),
        ];
    }
}
