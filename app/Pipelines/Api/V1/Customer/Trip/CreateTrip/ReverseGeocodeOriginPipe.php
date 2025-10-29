<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Services\GeocodingService;
use Closure;

/**
 * Reverse Geocode Origin Pipe
 *
 * Converts origin coordinates to location information
 */
class ReverseGeocodeOriginPipe
{
    public function __construct(
        protected GeocodingService $geocodingService,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        $context->originLocation = $this->geocodingService->reverseGeocode(
            $context->dto->originLatitude,
            $context->dto->originLongitude
        );

        return $next($context);
    }
}
