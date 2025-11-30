<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rider Settings Resource
 *
 * @property int $trip_request_timeout_seconds
 */
class RiderSettingsResource extends JsonResource
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
             * Trip request timeout in seconds
             *
             * @var int
             *
             * @example 240
             */
            'trip_request_timeout_seconds' => $this->resource['trip_request_timeout_seconds'],
        ];
    }
}
