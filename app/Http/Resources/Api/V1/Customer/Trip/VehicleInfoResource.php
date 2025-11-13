<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vehicle Info Resource
 *
 * Formats vehicle information for trip status response
 */
class VehicleInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Vehicle Model
             *
             * @example "Toyota Camry"
             *
             * @var string
             */
            'model' => $this->resource['model'],

            /**
             * Plate Number
             *
             * @example "12345"
             *
             * @var string
             */
            'plate_number' => $this->resource['plate_number'],
        ];
    }
}
