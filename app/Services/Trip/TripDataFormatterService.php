<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Models\TripRequest;

/**
 * Trip Data Formatter Service
 *
 * Formats trip request data for API responses
 */
readonly class TripDataFormatterService
{
    public function __construct(
        private TripRequestFormatterService $tripRequestFormatter,
        private TripLocationFormatterService $locationFormatter,
    ) {}

    /**
     * Prepare complete trip data for response
     *
     * @return array{trip_request: array, map_locations: array, formatted_locations: array, payment: array}
     */
    public function prepareTripData(TripRequest $tripRequest): array
    {
        // Ensure locations are loaded
        if (! $tripRequest->trip->relationLoaded('locations')) {
            $tripRequest->trip->load('locations');
        }

        return [
            'trip_request' => $this->tripRequestFormatter->prepareTripRequestData($tripRequest),
            'map_locations' => $this->locationFormatter->prepareMapLocations($tripRequest->trip),
            'formatted_locations' => $this->locationFormatter->prepareFormattedLocations($tripRequest->trip),
            'payment' => $this->tripRequestFormatter->preparePaymentData($tripRequest->trip),
        ];
    }
}
