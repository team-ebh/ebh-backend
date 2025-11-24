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
        $this->apiKey = config('services.google_maps.api_key');

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
            // Try to find a point of interest (POI) first
            $locationTitle = null;
            $formattedAddress = null;

            foreach ($data['results'] as $result) {
                // If this is a POI (point of interest), use its name
                $types = $result['types'] ?? [];
                if (in_array('point_of_interest', $types) || in_array('establishment', $types)) {
                    // Extract place name from address components
                    $locationTitle = $this->extractPlaceName($result['address_components'] ?? []);
                    if ($locationTitle && ! empty($result['formatted_address'])) {
                        $formattedAddress = $result['formatted_address'];

                        break;
                    }
                }
            }

            // If no POI found, use the first result
            if (! $locationTitle) {
                $result = $data['results'][0];
                $formattedAddress = $result['formatted_address'] ?? '';
                // Try to extract street address as title
                $locationTitle = $this->extractStreetAddress($result['address_components'] ?? []);

                // If still no title, use formatted address
                if (! $locationTitle) {
                    $locationTitle = $formattedAddress;
                }
            }

            if (empty($locationTitle)) {
                throw new InvalidGeocodingResponseException();
            }

            return [
                'location_title' => $locationTitle,
                'location_sub_title' => $formattedAddress,
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
     * Extract place name from address components
     *
     * Looks for establishment or point_of_interest name
     *
     * @param  array<array{types: array<string>, long_name: string}>  $addressComponents
     */
    private function extractPlaceName(array $addressComponents): ?string
    {
        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];
            if (in_array('establishment', $types) || in_array('point_of_interest', $types)) {
                return $component['long_name'] ?? null;
            }
        }

        return null;
    }

    /**
     * Extract street address from address components
     *
     * Combines route and street_number
     *
     * @param  array<array{types: array<string>, long_name: string}>  $addressComponents
     */
    private function extractStreetAddress(array $addressComponents): ?string
    {
        $streetNumber = null;
        $route = null;

        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];

            if (in_array('street_number', $types)) {
                $streetNumber = $component['long_name'] ?? null;
            }

            if (in_array('route', $types)) {
                $route = $component['long_name'] ?? null;
            }
        }

        if ($route) {
            return trim(($streetNumber ? $streetNumber . ' ' : '') . $route);
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
