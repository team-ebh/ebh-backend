<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Settings Resource
 *
 * @property int $arriving_at_poll_interval_seconds
 */
class CustomerSettingsResource extends JsonResource
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
             * Arriving at poll interval in seconds
             *
             * How often to poll the server for "arriving at" time updates
             *
             * @var int
             *
             * @example 10
             */
            'arriving_at_poll_interval_seconds' => $this->resource['arriving_at_poll_interval_seconds'],
        ];
    }
}
