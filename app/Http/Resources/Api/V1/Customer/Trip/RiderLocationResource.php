<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rider Location Resource
 */
class RiderLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Rider's current latitude coordinate
             *
             * @var float
             *
             * @example 29.37694
             */
            'latitude' => (float) $this->resource['latitude'],

            /**
             * Rider's current longitude coordinate
             *
             * @var float
             *
             * @example 47.98306
             */
            'longitude' => (float) $this->resource['longitude'],
        ];
    }
}
