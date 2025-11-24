<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Action Resource
 *
 * Formats trip action response data (arrived, pickup, complete)
 *
 * @property array $resource
 */
class TripActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Next action
             *
             * Indicates what action the rider should take next
             *
             * @var string|null
             *
             * @example "pickup"
             */
            'next_action' => $this->resource['next_action'] ?? null,

            /**
             * Trip completed flag
             *
             * Indicates if the entire trip has been completed (only present in complete action)
             *
             * @var bool|null
             *
             * @example true
             */
            'trip_completed' => $this->resource['trip_completed'] ?? false,
        ];
    }
}
