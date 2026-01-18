<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Settings Resource
 *
 * @property int $arriving_at_poll_interval_seconds
 * @property int $min_return_time_minutes
 * @property int $min_schedule_time_minutes
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

            /**
             * Minimum return time in minutes
             *
             * The minimum time (in minutes) that must pass before scheduling a return pickup in round trips
             *
             * @var int
             *
             * @example 60
             */
            'min_return_time_minutes' => $this->resource['min_return_time_minutes'],

            /**
             * Minimum schedule time in minutes
             *
             * The minimum time in advance (in minutes) that a customer can schedule a trip (5-1440 minutes)
             *
             * @var int
             *
             * @example 30
             */
            'min_schedule_time_minutes' => $this->resource['min_schedule_time_minutes'],
        ];
    }
}
