<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Services\GeocodingService;
use Closure;

/**
 * Reverse Geocode Destination Pipe
 *
 * Converts destination coordinates to location information
 */
class ReverseGeocodeDestinationPipe
{
    public function __construct(
        protected GeocodingService $geocodingService,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        // Only reverse geocode if title is not provided
        if (empty($context->dto->destinationLocationTitle)) {
            $context->destinationLocation = $this->geocodingService->reverseGeocode(
                $context->dto->destinationLatitude,
                $context->dto->destinationLongitude
            );
        } else {
            // Use provided location data
            $context->destinationLocation = [
                'location_title' => $context->dto->destinationLocationTitle,
                'location_sub_title' => $context->dto->destinationLocationSubTitle,
            ];
        }

        return $next($context);
    }
}
