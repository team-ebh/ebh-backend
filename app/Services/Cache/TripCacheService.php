<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Models\Trip;
use App\Services\RealTimeCache\RealTimeCacheManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Trip Cache Service
 *
 * Manages caching of trip data including active trips and trip metadata
 */
class TripCacheService
{
    public const string CACHE_PREFIX = 'trip:';

    public const string CACHE_ACTIVE_TRIPS_KEY = 'trips:active';

    public const int CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private readonly RealTimeCacheManager $rtCache
    ) {}

    /**
     * Update cache for a single trip
     */
    public function updateTrip(int $tripId): bool
    {
        try {
            $trip = Trip::query()
                ->where(Trip::COLUMN_ID, $tripId)
                ->with([
                    'customer:id,full_name,phone_number',
                    'rider:id,full_name,phone_number',
                    'locations',
                ])
                ->first();

            if (! $trip) {
                $this->removeTrip($tripId);

                return false;
            }

            // Cache trip data
            Cache::put(
                self::CACHE_PREFIX . $tripId,
                $this->transformTripData($trip),
                self::CACHE_TTL
            );

            // Update in RealTimeCache if trip is active
            if (! $trip->isCompleted() && ! $trip->isCanceledByCustomer() && ! $trip->isCancelledByRider()) {
                $this->rtCache->trip()->store(
                    $tripId,
                    $this->transformTripDataForRtCache($trip),
                    $trip->{Trip::COLUMN_STATUS}->value
                );
            } else {
                // Remove completed/cancelled trips from RealTimeCache
                $this->rtCache->trip()->forget($tripId);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to update trip cache', [
                'trip_id' => $tripId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update cache for all active trips
     */
    public function updateActiveTrips(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        $trips = Trip::query()
            ->activeTrips()
            ->with([
                'customer:id,full_name,phone_number',
                'rider:id,full_name,phone_number',
                'locations',
            ])
            ->get();

        $stats['total'] = $trips->count();

        foreach ($trips as $trip) {
            if ($this->updateTrip($trip->{Trip::COLUMN_ID})) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        // Update active trips list cache
        Cache::put(
            self::CACHE_ACTIVE_TRIPS_KEY,
            $trips->pluck(Trip::COLUMN_ID)->toArray(),
            self::CACHE_TTL
        );

        return $stats;
    }

    /**
     * Update all trips (including completed)
     */
    public function updateAllTrips(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        // Only cache recent trips (last 7 days)
        $trips = Trip::query()
            ->where(Trip::COLUMN_CREATED_AT, '>=', now()->subDays(7))
            ->with([
                'customer:id,full_name,phone_number',
                'rider:id,full_name,phone_number',
                'locations',
            ])
            ->get();

        $stats['total'] = $trips->count();

        foreach ($trips as $trip) {
            if ($this->updateTrip($trip->{Trip::COLUMN_ID})) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Remove trip from cache
     */
    public function removeTrip(int $tripId): void
    {
        Cache::forget(self::CACHE_PREFIX . $tripId);
        $this->rtCache->trip()->forget($tripId);
    }

    /**
     * Get trip from cache
     */
    public function getTrip(int $tripId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $tripId);
    }

    /**
     * Flush all trip caches
     */
    public function flush(): void
    {
        $tripIds = Cache::get(self::CACHE_ACTIVE_TRIPS_KEY, []);

        foreach ($tripIds as $tripId) {
            $this->removeTrip($tripId);
        }

        Cache::forget(self::CACHE_ACTIVE_TRIPS_KEY);
        $this->rtCache->trip()->flush();
    }

    /**
     * Transform trip data for caching
     */
    private function transformTripData(Trip $trip): array
    {
        return [
            'id' => $trip->{Trip::COLUMN_ID},
            'customer_id' => $trip->{Trip::COLUMN_CUSTOMER_ID},
            'rider_id' => $trip->{Trip::COLUMN_RIDER_ID},
            'status' => $trip->{Trip::COLUMN_STATUS}->value,
            'trip_type' => $trip->{Trip::COLUMN_TRIP_TYPE_ID}->value,
            'ride_type' => $trip->{Trip::COLUMN_RIDE_TYPE}->value,
            'total_price' => $trip->{Trip::COLUMN_TOTAL_PRICE},
            'currency' => $trip->{Trip::COLUMN_CURRENCY}->value,
            'scheduled_time' => $trip->{Trip::COLUMN_SCHEDULED_TIME}?->timestamp,
            'customer' => $trip->customer ? [
                'id' => $trip->customer->id,
                'full_name' => $trip->customer->full_name,
                'phone_number' => $trip->customer->phone_number,
            ] : null,
            'rider' => $trip->rider ? [
                'id' => $trip->rider->id,
                'full_name' => $trip->rider->full_name,
                'phone_number' => $trip->rider->phone_number,
            ] : null,
            'locations_count' => $trip->locations->count(),
            'created_at' => $trip->{Trip::COLUMN_CREATED_AT}->timestamp,
            'cached_at' => now()->timestamp,
        ];
    }

    /**
     * Transform trip data for RealTimeCache
     */
    private function transformTripDataForRtCache(Trip $trip): array
    {
        return [
            'trip_id' => $trip->{Trip::COLUMN_ID},
            'customer_id' => $trip->{Trip::COLUMN_CUSTOMER_ID},
            'rider_id' => $trip->{Trip::COLUMN_RIDER_ID},
            'status' => $trip->{Trip::COLUMN_STATUS}->value,
            'locations_count' => $trip->locations->count(),
        ];
    }
}
