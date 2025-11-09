<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Confirm Trip Resource
 *
 * Formats confirmed trip response for API
 */
class ConfirmTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Status
             *
             * Current trip status (should be "CONFIRMED" - searching for taxi)
             *
             * @example "Confirmed"
             *
             * @var string
             */
            'status' => $this->resource->status->getLabel(),
        ];
    }
}
