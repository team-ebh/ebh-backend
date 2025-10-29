<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Location Resource
 *
 * Formats location data for API responses (origin or destination)
 */
class TripLocationResource extends JsonResource
{
    public function __construct(
        private readonly string $location,
        private readonly ?string $subLocation,
        private readonly float $latitude,
        private readonly float $longitude,
    ) {
        parent::__construct([]);
    }

    public function toArray(Request $request): array
    {
        return [
            /**
             * Main location name
             *
             * @example "Kuwait Hospital"
             *
             * @var string
             */
            'location' => $this->location,

            /**
             * Sub-location name
             *
             * @example "Sabah medical district"
             *
             * @var string|null
             */
            'sub_location' => $this->subLocation,

            /**
             * Latitude coordinate
             *
             * @example 29.12345
             *
             * @var float
             */
            'lat' => $this->latitude,

            /**
             * Longitude coordinate
             *
             * @example 47.56789
             *
             * @var float
             */
            'lng' => $this->longitude,
        ];
    }
}
