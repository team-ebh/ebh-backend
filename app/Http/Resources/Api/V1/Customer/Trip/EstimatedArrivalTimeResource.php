<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estimated Arrival Time Resource
 *
 * Formats estimated arrival time data for customer API responses
 *
 * @property array $resource
 */
class EstimatedArrivalTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Estimated arrival time in seconds
             *
             * @var int
             *
             * @example 137
             */
            'estimated_arrival_seconds' => $this->resource['estimated_arrival_seconds'],
        ];
    }
}
