<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Models\TripLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TripLocation Cache Service
 *
 * Manages caching of trip location data
 */
class TripLocationCacheService
{
    public const string CACHE_PREFIX = 'trip_location:';

    public const string CACHE_BY_TRIP_PREFIX = 'trip_locations:trip:';

    public const int CACHE_TTL = 1800; // 30 minutes

    /**
     * Update cache for a single trip location
     */
    public function updateTripLocation(int $locationId): bool
    {
        try {
            $location = TripLocation::query()
                ->where(TripLocation::COLUMN_ID, $locationId)
                ->with(['trip'])
                ->first();

            if (! $location) {
                $this->removeTripLocation($locationId);

                return false;
            }

            // Cache location data
            Cache::put(
                self::CACHE_PREFIX . $locationId,
                $this->transformLocationData($location),
                self::CACHE_TTL
            );

            // Update trip locations cache
            $this->updateTripLocationsCache($location->{TripLocation::COLUMN_TRIP_ID});

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to update trip location cache', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update cache for all locations of a trip
     */
    public function updateTripLocations(int $tripId): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        $locations = TripLocation::query()
            ->where(TripLocation::COLUMN_TRIP_ID, $tripId)
            ->orderBy(TripLocation::COLUMN_SEQUENCE)
            ->get();

        $stats['total'] = $locations->count();

        foreach ($locations as $location) {
            if ($this->updateTripLocation($location->{TripLocation::COLUMN_ID})) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Update cache for all trip locations
     */
    public function updateAllTripLocations(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        // Only cache recent trip locations (last 7 days)
        $locations = TripLocation::query()
            ->whereHas('trip', function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            })
            ->with(['trip'])
            ->get();

        $stats['total'] = $locations->count();

        foreach ($locations as $location) {
            if ($this->updateTripLocation($location->{TripLocation::COLUMN_ID})) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Remove trip location from cache
     */
    public function removeTripLocation(int $locationId): void
    {
        $location = Cache::get(self::CACHE_PREFIX . $locationId);

        Cache::forget(self::CACHE_PREFIX . $locationId);

        if ($location && isset($location['trip_id'])) {
            $this->updateTripLocationsCache($location['trip_id']);
        }
    }

    /**
     * Get trip location from cache
     */
    public function getTripLocation(int $locationId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $locationId);
    }

    /**
     * Get all locations for a trip from cache
     */
    public function getTripLocations(int $tripId): array
    {
        return Cache::get(self::CACHE_BY_TRIP_PREFIX . $tripId, []);
    }

    /**
     * Flush all trip location caches
     */
    public function flush(): void
    {
        // This is a simplified flush - in production you might want to track all cached location IDs
        Cache::flush();
    }

    /**
     * Transform location data for caching
     */
    private function transformLocationData(TripLocation $location): array
    {
        return [
            'id' => $location->{TripLocation::COLUMN_ID},
            'trip_id' => $location->{TripLocation::COLUMN_TRIP_ID},
            'location_title' => $location->{TripLocation::COLUMN_LOCATION_TITLE},
            'location_sub_title' => $location->{TripLocation::COLUMN_LOCATION_SUB_TITLE},
            'latitude' => $location->{TripLocation::COLUMN_LATITUDE},
            'longitude' => $location->{TripLocation::COLUMN_LONGITUDE},
            'type' => $location->{TripLocation::COLUMN_TYPE}->value,
            'sequence' => $location->{TripLocation::COLUMN_SEQUENCE},
            'status' => $location->{TripLocation::COLUMN_STATUS}->value,
            'cached_at' => now()->timestamp,
        ];
    }

    /**
     * Update the cache of all locations for a specific trip
     */
    private function updateTripLocationsCache(int $tripId): void
    {
        $locations = TripLocation::query()
            ->where(TripLocation::COLUMN_TRIP_ID, $tripId)
            ->orderBy(TripLocation::COLUMN_SEQUENCE)
            ->get()
            ->map(fn ($location) => $this->transformLocationData($location))
            ->toArray();

        Cache::put(
            self::CACHE_BY_TRIP_PREFIX . $tripId,
            $locations,
            self::CACHE_TTL
        );
    }
}
