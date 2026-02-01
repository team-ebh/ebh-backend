<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Database;

use App\Services\RealTimeCache\Contracts\TripStorageInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Database Trip Storage (Fallback)
 *
 * Uses Laravel's cache (file/database) for trip caching when Redis is unavailable.
 * Trip data is also synced to the Trip model.
 */
class DatabaseTripStorage implements TripStorageInterface
{
    private const string CACHE_PREFIX = 'rtc_trip:';

    private const string RIDER_TRIP_PREFIX = 'rtc_rider_trip:';

    private const string CUSTOMER_TRIP_PREFIX = 'rtc_customer_trip:';

    private const string ACTIVE_TRIPS_KEY = 'rtc_active_trips';

    private readonly array $ttlConfig;

    public function __construct()
    {
        $this->ttlConfig = config('realtime-cache.trip.ttl', []);
    }

    public function store(int $tripId, array $data, string $status = 'pending'): void
    {
        $ttl = $this->getTtlForStatus($status);
        $data['_status'] = $status;
        $data['_created_at'] = time();

        Cache::put(self::CACHE_PREFIX . $tripId, $data, $ttl);

        // Track active trips
        $activeTrips = Cache::get(self::ACTIVE_TRIPS_KEY, []);
        $activeTrips[$tripId] = true;
        Cache::put(self::ACTIVE_TRIPS_KEY, $activeTrips, 86400);
    }

    public function get(int $tripId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $tripId);
    }

    public function update(int $tripId, array $data): void
    {
        $existing = $this->get($tripId);

        if (! $existing) {
            return;
        }

        $data['_updated_at'] = time();
        $merged = array_merge($existing, $data);

        $status = $merged['_status'] ?? 'pending';
        $ttl = $this->getTtlForStatus($status);

        Cache::put(self::CACHE_PREFIX . $tripId, $merged, $ttl);
    }

    public function updateStatus(int $tripId, string $status): void
    {
        $existing = $this->get($tripId);

        if (! $existing) {
            return;
        }

        $existing['_status'] = $status;
        $existing['_updated_at'] = time();
        $ttl = $this->getTtlForStatus($status);

        Cache::put(self::CACHE_PREFIX . $tripId, $existing, $ttl);
    }

    public function remove(int $tripId): void
    {
        $data = $this->get($tripId);

        Cache::forget(self::CACHE_PREFIX . $tripId);

        // Remove from active trips
        $activeTrips = Cache::get(self::ACTIVE_TRIPS_KEY, []);
        unset($activeTrips[$tripId]);
        Cache::put(self::ACTIVE_TRIPS_KEY, $activeTrips, 86400);

        // Clear mappings
        if (isset($data['rider_id'])) {
            Cache::forget(self::RIDER_TRIP_PREFIX . $data['rider_id']);
        }
        if (isset($data['customer_id'])) {
            Cache::forget(self::CUSTOMER_TRIP_PREFIX . $data['customer_id']);
        }
    }

    public function exists(int $tripId): bool
    {
        return Cache::has(self::CACHE_PREFIX . $tripId);
    }

    public function setRiderActiveTrip(int $riderId, int $tripId): void
    {
        Cache::put(self::RIDER_TRIP_PREFIX . $riderId, $tripId, 86400);
    }

    public function getRiderActiveTrip(int $riderId): ?int
    {
        $tripId = Cache::get(self::RIDER_TRIP_PREFIX . $riderId);

        return $tripId !== null ? (int) $tripId : null;
    }

    public function clearRiderActiveTrip(int $riderId): void
    {
        Cache::forget(self::RIDER_TRIP_PREFIX . $riderId);
    }

    public function setCustomerActiveTrip(int $customerId, int $tripId): void
    {
        Cache::put(self::CUSTOMER_TRIP_PREFIX . $customerId, $tripId, 86400);
    }

    public function getCustomerActiveTrip(int $customerId): ?int
    {
        $tripId = Cache::get(self::CUSTOMER_TRIP_PREFIX . $customerId);

        return $tripId !== null ? (int) $tripId : null;
    }

    public function clearCustomerActiveTrip(int $customerId): void
    {
        Cache::forget(self::CUSTOMER_TRIP_PREFIX . $customerId);
    }

    public function getAllTripIds(): array
    {
        $activeTrips = Cache::get(self::ACTIVE_TRIPS_KEY, []);
        $validIds = [];

        foreach (array_keys($activeTrips) as $tripId) {
            if ($this->exists((int) $tripId)) {
                $validIds[] = (int) $tripId;
            }
        }

        return $validIds;
    }

    public function count(): int
    {
        return count($this->getAllTripIds());
    }

    public function flush(): void
    {
        $activeTrips = Cache::get(self::ACTIVE_TRIPS_KEY, []);

        foreach (array_keys($activeTrips) as $tripId) {
            Cache::forget(self::CACHE_PREFIX . $tripId);
        }

        Cache::forget(self::ACTIVE_TRIPS_KEY);
    }

    private function getTtlForStatus(string $status): int
    {
        return $this->ttlConfig[$status] ?? 3600;
    }
}
