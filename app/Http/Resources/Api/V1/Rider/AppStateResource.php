<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App State Resource
 *
 * Formats rider app state response
 */
class AppStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * State
             *
             * Current app state
             *
             * Possible values:
             * - OFFLINE: Rider is offline
             * - ONLINE_IDLE: Rider is online but has no active trip
             * - HAS_ACTIVE_TRIP: Rider has an active trip
             *
             * @example HAS_ACTIVE_TRIP
             *
             * @var string
             */
            'state' => $this->resource->value,
        ];
    }
}
