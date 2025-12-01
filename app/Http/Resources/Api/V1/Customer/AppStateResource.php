<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App State Resource
 *
 * Formats customer app state response
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
             * - NO_TRIP: Customer has no active trip
             * - HAS_ACTIVE_TRIP: Customer has an active trip
             *
             * @example HAS_ACTIVE_TRIP
             *
             * @var string
             */
            'state' => $this->resource->value,
        ];
    }
}
