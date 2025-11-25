<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estimated Arrival Time Resource
 *
 * Formats estimated arrival time data for rider API responses
 *
 * @property array $resource
 */
class EstimatedArrivalTimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Estimated Arrival Time in Seconds
             *
             * The estimated time in seconds for the rider to reach the next location
             *
             * @var int
             *
             * @example 180
             */
            'estimated_arrival_seconds' => $this->resource['estimated_arrival_seconds'],
        ];
    }
}
