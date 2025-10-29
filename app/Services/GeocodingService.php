<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BaseException;
use App\Exceptions\GeocodingApiErrorException;
use App\Exceptions\GeocodingFailedException;
use App\Exceptions\InvalidGeocodingResponseException;
use App\Exceptions\NoGeocodingResultsException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding Service
 *
 * Handles geocoding operations using Google Maps Geocoding API
 */
class GeocodingService
{
    private const string GOOGLE_GEOCODING_API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private readonly string $apiKey;

    /**
     * Create a new geocoding service instance
     */
    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key', env('GOOGLE_MAPS_API_KEY', ''));

        if (empty($this->apiKey)) {
            Log::error('Google Maps API key is not configured');
        }
    }

    /**
     * Reverse geocode coordinates to location information
     *
     * @param  float  $latitude  Latitude coordinate
     * @param  float  $longitude  Longitude coordinate
     * @return array{location_title: string, location_sub_title: string|null}
     *
     * @throws GeocodingFailedException
     * @throws InvalidGeocodingResponseException
     * @throws NoGeocodingResultsException
     * @throws GeocodingApiErrorException
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        if (empty($this->apiKey)) {
            throw new GeocodingFailedException();
        }

        try {
            $response = Http::timeout(10)
                ->get(self::GOOGLE_GEOCODING_API_URL, [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $this->apiKey,
                    'language' => app()->getLocale(),
                ]);

            if ($response->failed()) {
                Log::error('Reverse geocoding API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new GeocodingFailedException();
            }

            $data = $response->json();

            if (! isset($data['status'])) {
                throw new InvalidGeocodingResponseException();
            }

            if ($data['status'] === 'ZERO_RESULTS') {
                throw new NoGeocodingResultsException();
            }

            if ($data['status'] !== 'OK') {
                $errorMessage = $data['error_message'] ?? $data['status'];
                Log::error('Reverse geocoding API error', [
                    'status' => $data['status'],
                    'error' => $errorMessage,
                ]);

                throw new GeocodingApiErrorException($errorMessage);
            }

            if (empty($data['results'])) {
                throw new NoGeocodingResultsException();
            }

            // Extract location information from the results
            $result = $data['results'][0];

            // Main location title (formatted address)
            $locationTitle = $result['formatted_address'] ?? '';

            if (empty($locationTitle)) {
                throw new InvalidGeocodingResponseException();
            }

            // Extract sub-location (neighborhood, sublocality, or locality)
            $locationSubTitle = $this->extractSubLocation($result['address_components'] ?? []);

            return [
                'location_title' => $locationTitle,
                'location_sub_title' => $locationSubTitle,
            ];
        } catch (BaseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Reverse geocoding service exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new GeocodingFailedException();
        }
    }

    /**
     * Extract sub-location from address components
     *
     * Prioritizes: neighborhood > sublocality > locality > administrative_area_level_1
     *
     * @param  array<array{types: array<string>, long_name: string}>  $addressComponents
     */
    private function extractSubLocation(array $addressComponents): ?string
    {
        $priorities = [
            'neighborhood',
            'sublocality_level_1',
            'sublocality',
            'locality',
            'administrative_area_level_1',
        ];

        foreach ($priorities as $type) {
            foreach ($addressComponents as $component) {
                if (in_array($type, $component['types'] ?? [], true)) {
                    return $component['long_name'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Batch reverse geocode multiple coordinates
     *
     * @param  array<array{latitude: float, longitude: float}>  $coordinates
     * @return array<array{location_title: string, location_sub_title: string|null}>
     *
     * @throws GeocodingFailedException
     * @throws InvalidGeocodingResponseException
     * @throws NoGeocodingResultsException
     * @throws GeocodingApiErrorException
     */
    public function batchReverseGeocode(array $coordinates): array
    {
        $results = [];

        foreach ($coordinates as $coordinate) {
            $results[] = $this->reverseGeocode(
                $coordinate['latitude'],
                $coordinate['longitude']
            );
        }

        return $results;
    }
}
