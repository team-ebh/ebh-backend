<?php

declare(strict_types=1);

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripRequest;

/**
 * Trip Request Formatter Service
 *
 * Handles formatting trip request data for API responses
 */
class TripRequestFormatterService
{
    /**
     * Prepare trip request data
     */
    public function prepareTripRequestData(TripRequest $tripRequest): array
    {
        return [
            'trip_id' => $tripRequest->trip->{Trip::COLUMN_ID},
            'trip_request_id' => $tripRequest->{TripRequest::COLUMN_ID},
            'distance' => $tripRequest->{TripRequest::COLUMN_DISTANCE_METERS},
            'eta' => $tripRequest->{TripRequest::COLUMN_ESTIMATED_ARRIVAL_SECONDS},
            'arrived_at' => $tripRequest->{TripRequest::COLUMN_ARRIVED_AT},
            'passenger_count' => $tripRequest->trip->{Trip::COLUMN_PASSENGER_COUNT},
            'trip_accessibility' => $tripRequest->trip->loadMissing('accessibility')->accessibility,
        ];
    }

    /**
     * Prepare payment data
     */
    public function preparePaymentData(Trip $trip): string
    {
        return priceFormat($trip->{Trip::COLUMN_TOTAL_PRICE})
            . ' ' . $trip->{Trip::COLUMN_CURRENCY}->getLabel();
    }
}
