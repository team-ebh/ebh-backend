<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Distance Calculation Service
 *
 * Handles distance and duration calculations using Google Maps Distance Matrix API
 */
class DistanceCalculationService
{
    private const string GOOGLE_DISTANCE_MATRIX_API_URL = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    private readonly string $apiKey;

    /**
     * Create a new distance calculation service instance
     */
    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key', env('GOOGLE_MAPS_API_KEY', ''));

        if (empty($this->apiKey)) {
            Log::warning('Google Maps API key is not configured');
        }
    }

    /**
     * Calculate distance and estimated arrival time between two points
     *
     * @param  float  $originLatitude  Origin latitude
     * @param  float  $originLongitude  Origin longitude
     * @param  float  $destinationLatitude  Destination latitude
     * @param  float  $destinationLongitude  Destination longitude
     * @return array{distance_meters: int|null, estimated_arrival_minutes: int|null}
     */
    public function calculateDistanceAndDuration(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): array {
        // If API key is not configured, fall back to Haversine formula
        if (empty($this->apiKey)) {
            return $this->calculateUsingHaversine(
                $originLatitude,
                $originLongitude,
                $destinationLatitude,
                $destinationLongitude
            );
        }

        try {
            $response = Http::timeout(10)
                ->get(self::GOOGLE_DISTANCE_MATRIX_API_URL, [
                    'origins' => "{$originLatitude},{$originLongitude}",
                    'destinations' => "{$destinationLatitude},{$destinationLongitude}",
                    'key' => $this->apiKey,
                    'mode' => 'driving',
                    'units' => 'metric',
                    'language' => app()->getLocale(),
                ]);

            if ($response->failed()) {
                Log::error('Distance Matrix API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->calculateUsingHaversine(
                    $originLatitude,
                    $originLongitude,
                    $destinationLatitude,
                    $destinationLongitude
                );
            }

            $data = $response->json();

            if (! isset($data['status']) || $data['status'] !== 'OK') {
                Log::error('Distance Matrix API error', [
                    'status' => $data['status'] ?? 'unknown',
                    'error' => $data['error_message'] ?? 'Unknown error',
                ]);

                return $this->calculateUsingHaversine(
                    $originLatitude,
                    $originLongitude,
                    $destinationLatitude,
                    $destinationLongitude
                );
            }

            $element = $data['rows'][0]['elements'][0] ?? null;

            if (! $element || $element['status'] !== 'OK') {
                Log::warning('Distance Matrix element not OK', [
                    'element_status' => $element['status'] ?? 'unknown',
                ]);

                return $this->calculateUsingHaversine(
                    $originLatitude,
                    $originLongitude,
                    $destinationLatitude,
                    $destinationLongitude
                );
            }

            $distanceMeters = $element['distance']['value'] ?? null;
            $durationSeconds = $element['duration']['value'] ?? null;

            return [
                'distance_meters' => $distanceMeters ? (int) $distanceMeters : null,
                'estimated_arrival_minutes' => $durationSeconds ? (int) ceil($durationSeconds / 60) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Distance calculation service exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->calculateUsingHaversine(
                $originLatitude,
                $originLongitude,
                $destinationLatitude,
                $destinationLongitude
            );
        }
    }

    /**
     * Calculate distance using Haversine formula (fallback method)
     *
     * This method calculates the great-circle distance between two points
     * and estimates duration based on average speed
     *
     * @param  float  $originLatitude  Origin latitude
     * @param  float  $originLongitude  Origin longitude
     * @param  float  $destinationLatitude  Destination latitude
     * @param  float  $destinationLongitude  Destination longitude
     * @return array{distance_meters: int|null, estimated_arrival_minutes: int|null}
     */
    private function calculateUsingHaversine(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): array {
        $earthRadiusMeters = 6371000;

        $latFrom = deg2rad($originLatitude);
        $lonFrom = deg2rad($originLongitude);
        $latTo = deg2rad($destinationLatitude);
        $lonTo = deg2rad($destinationLongitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distanceMeters = (int) round($earthRadiusMeters * $c);

        // Estimate duration based on average city speed (30 km/h = 8.33 m/s)
        $averageSpeedMetersPerSecond = 8.33;
        $durationSeconds = $distanceMeters / $averageSpeedMetersPerSecond;
        $estimatedArrivalMinutes = (int) ceil($durationSeconds / 60);

        return [
            'distance_meters' => $distanceMeters,
            'estimated_arrival_minutes' => $estimatedArrivalMinutes,
        ];
    }

    /**
     * Calculate distance and duration for multiple origin-destination pairs
     *
     * @param  array<array{origin_latitude: float, origin_longitude: float, destination_latitude: float, destination_longitude: float}>  $pairs
     * @return array<array{distance_meters: int|null, estimated_arrival_minutes: int|null}>
     */
    public function batchCalculate(array $pairs): array
    {
        $results = [];

        foreach ($pairs as $pair) {
            $results[] = $this->calculateDistanceAndDuration(
                $pair['origin_latitude'],
                $pair['origin_longitude'],
                $pair['destination_latitude'],
                $pair['destination_longitude']
            );
        }

        return $results;
    }
}
