<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formatted Location Resource
 *
 * Formats location pairs as from/to structure with full details
 */
class FormattedLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Is Active
             *
             * Whether this route segment is currently active/in progress
             *
             * @example true
             *
             * @var bool
             */
            'is_active' => $this->resource['is_active'] ?? true,

            /**
             * From Location
             *
             * Starting point of this segment
             *
             * @var LocationPointResource
             */
            'from' => new LocationPointResource($this->resource['from']),

            /**
             * To Location
             *
             * Destination point of this segment
             *
             * @var LocationPointResource
             */
            'to' => new LocationPointResource($this->resource['to']),
        ];
    }
}
