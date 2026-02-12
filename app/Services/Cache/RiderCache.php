<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use App\Repositories\Api\V1\Rider\RiderRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Rider Cache
 *
 * Stores rider data (status, location, etc.) with geospatial search
 *
 * Usage:
 * - RiderCache::rider($id, fn() => [...])
 * - RiderCache::findNearby($lat, $lng, $radius)
 * - RiderCache::getOnline()
 * - RiderCache::getBusy()
 * - RiderCache::isOnline($riderId)
 * - RiderCache::isBusy($riderId)
 */
class RiderCache extends BaseCache
{
    protected function scope(): string
    {
        return 'rider';
    }

    protected function ttl(): int
    {
        return 60; // 60 seconds
    }

    /**
     * Override get() to also store in GEO set when has location
     * Cache fallback handled by parent BaseCache::get()
     * Supports both formats: lat/lng and latitude/longitude
     */
    public function get($id, callable $callback)
    {
        // Get data from cache (BaseCache handles fallback)
        $data = parent::get($id, $callback);

        // Store in Redis GEO set if has location (supports both formats)
        if ($this->isRedisDriver()) {
            // Support both lat/lng and latitude/longitude formats
            $lat = $data['lat'] ?? $data[Rider::COLUMN_LATITUDE] ?? null;
            $lng = $data['lng'] ?? $data[Rider::COLUMN_LONGITUDE] ?? null;

            if ($lat !== null && $lng !== null) {
                safeProcess()
                    ->onFailed(fn () => null) // GEO set is optional, ignore if fails
                    ->do(fn () => $this->storeInGeoSet($id, (float) $lat, (float) $lng));
            }
        }

        return $data;
    }

    /**
     * Override forget() to also remove from GEO set
     */
    public function forget($id): void
    {
        parent::forget($id);

        if ($this->isRedisDriver()) {
            $cacheConnection = config('cache.stores.redis.connection', 'cache');
            Redis::connection($cacheConnection)->zrem($this->geoKey(), (string) $id);
        }
    }

    /**
     * Find nearby riders within radius
     * Primary: Redis GEORADIUS, Fallback: Database Haversine via Repository
     *
     * @throws \Throwable
     */
    public static function findNearby(float $lat, float $lng, int $radiusMeters): array
    {
        $instance = new static;

        return safeProcess()
            ->onFailed(
                fn () => app(RiderRepository::class)
                    ->findNearby($lat, $lng, $radiusMeters)
                    ->map(fn ($rider) => [
                        'id' => $rider->id,
                        'name' => $rider->full_name,
                        'phone' => $rider->phone_number,
                        'status' => $rider->status,
                        'lat' => $rider->latitude,
                        'lng' => $rider->longitude,
                        'distance' => $rider->distance * 1000, // convert to meters
                    ])
                    ->toArray()
            )
            ->do(fn () => $instance->findNearbyRiders($lat, $lng, $radiusMeters));
    }

    /**
     * Get all online riders
     * Primary: Redis Cache, Fallback: Database via Repository
     */
    public static function getOnline(): array
    {
        $instance = new static;

        return safeProcess()
            ->onFailed(
                fn () => app(RiderRepository::class)
                    ->getByStatus(RiderStatusEnum::ONLINE->value)
                    ->map(fn ($rider) => [
                        'id' => $rider->id,
                        'name' => $rider->full_name,
                        'phone' => $rider->phone_number,
                        'status' => $rider->status,
                        'lat' => $rider->latitude,
                        'lng' => $rider->longitude,
                    ])
                    ->toArray()
            )
            ->do(fn () => $instance->getRidersByStatus(RiderStatusEnum::ONLINE->value));
    }

    /**
     * Get all busy riders
     * Primary: Redis Cache, Fallback: Database via Repository
     */
    public static function getBusy(): array
    {
        $instance = new static;

        return safeProcess()
            ->onFailed(
                fn () => app(RiderRepository::class)
                    ->getByStatus(RiderStatusEnum::BUSY->value)
                    ->map(fn ($rider) => [
                        'id' => $rider->id,
                        'name' => $rider->full_name,
                        'phone' => $rider->phone_number,
                        'status' => $rider->status,
                        'lat' => $rider->latitude,
                        'lng' => $rider->longitude,
                    ])
                    ->toArray()
            )
            ->do(fn () => $instance->getRidersByStatus(RiderStatusEnum::BUSY->value));
    }

    /**
     * Check if rider is online
     * Primary: Redis Cache, Fallback: Database via Repository
     */
    public static function isOnline(int $riderId): bool
    {
        return safeProcess()
            ->onFailed(function () use ($riderId) {
                $rider = app(RiderRepository::class)->find($riderId);

                return $rider && $rider->status === RiderStatusEnum::ONLINE->value;
            })
            ->do(function () use ($riderId) {
                $data = static::rider($riderId, fn () => null);

                return $data && ($data['status'] ?? '') === RiderStatusEnum::ONLINE->value;
            });
    }

    /**
     * Check if rider is busy
     * Primary: Redis Cache, Fallback: Database via Repository
     */
    public static function isBusy(int $riderId): bool
    {
        return safeProcess()
            ->onFailed(function () use ($riderId) {
                $rider = app(RiderRepository::class)->find($riderId);

                return $rider && $rider->status === RiderStatusEnum::BUSY->value;
            })
            ->do(function () use ($riderId) {
                $data = static::rider($riderId, fn () => null);

                return $data && ($data['status'] ?? '') === RiderStatusEnum::BUSY->value;
            });
    }

    /**
     * Get total count of riders in cache
     * Primary: Redis, Fallback: Database via Repository
     */
    public static function count(): int
    {
        return safeProcess()
            ->onFailed(
                fn () => app(RiderRepository::class)
                    ->getByStatus(RiderStatusEnum::ONLINE->value)
                    ->count() + app(RiderRepository::class)
                    ->getByStatus(RiderStatusEnum::BUSY->value)
                    ->count()
            )
            ->do(fn () => count((new static)->getAllRiderIds()));
    }

    /**
     * Get count by status
     * Primary: Redis, Fallback: Database via Repository
     */
    public static function countByStatus(string $status): int
    {
        $instance = new static;

        return safeProcess()
            ->onFailed(
                fn () => app(RiderRepository::class)
                    ->getByStatus($status)
                    ->count()
            )
            ->do(fn () => count($instance->getRidersByStatus($status)));
    }

    /**
     * Get count from cache ONLY (no database fallback)
     * Returns 0 if cache is empty or error occurs
     */
    public static function countCacheOnly(): int
    {
        return safeProcess()
            ->onFailed(fn () => 0)
            ->do(fn () => count((new static)->getAllRiderIds()));
    }

    /**
     * Get count by status from cache ONLY (no database fallback)
     * Returns 0 if cache is empty or error occurs
     */
    public static function countByStatusCacheOnly(string $status): int
    {
        return safeProcess()
            ->onFailed(fn () => 0)
            ->do(fn () => count((new static)->getRidersByStatus($status)));
    }

    /**
     * Get online riders from cache ONLY (no database fallback)
     * Returns empty array if cache is empty or error occurs
     */
    public static function getOnlineCacheOnly(): array
    {
        return safeProcess()
            ->onFailed(fn () => [])
            ->do(fn () => (new static)->getRidersByStatus(RiderStatusEnum::ONLINE->value));
    }

    /**
     * Get busy riders from cache ONLY (no database fallback)
     * Returns empty array if cache is empty or error occurs
     */
    public static function getBusyCacheOnly(): array
    {
        return safeProcess()
            ->onFailed(fn () => [])
            ->do(fn () => (new static)->getRidersByStatus(RiderStatusEnum::BUSY->value));
    }

    /**
     * Flush all riders + GEO set
     *
     * Note: Group flush only works with tag-supporting drivers (Redis, Memcached)
     */
    public static function flush(): void
    {
        $instance = new static;

        if (! $instance->enabled()) {
            return;
        }

        // Only flush if tags are supported
        if ($instance->supportsTags()) {
            Cache::tags([$instance->scope()])->flush();
        }

        // Remove GEO set if Redis
        if ($instance->isRedisDriver()) {
            $cacheConnection = config('cache.stores.redis.connection', 'cache');
            Redis::connection($cacheConnection)->del($instance->geoKey());
        }
    }

    // ==================== Private Methods ====================

    private function findNearbyRiders(float $lat, float $lng, int $radiusMeters): array
    {
        if ($this->isRedisDriver()) {
            return $this->findNearbyRedis($lat, $lng, $radiusMeters);
        }

        return $this->findNearbyInMemory($lat, $lng, $radiusMeters);
    }

    private function getRidersByStatus(string $status): array
    {
        if ($this->isRedisDriver()) {
            return $this->getRidersByStatusRedis($status);
        }

        return $this->getRidersByStatusInMemory($status);
    }

    private function findNearbyRedis(float $lat, float $lng, int $radiusMeters): array
    {
        $cacheConnection = config('cache.stores.redis.connection', 'cache');
        $riders = Redis::connection($cacheConnection)->georadius(
            $this->geoKey(),
            $lng,
            $lat,
            $radiusMeters,
            'm',
            ['WITHDIST']
        );

        $result = [];
        foreach ($riders as $rider) {
            $riderId = (int) $rider[0];
            $distance = (float) $rider[1];

            $data = $this->getRiderData($riderId);

            if ($data) {
                $result[] = array_merge($data, ['distance' => $distance]);
            }
        }

        return $result;
    }

    private function findNearbyInMemory(float $lat, float $lng, int $radiusMeters): array
    {
        $allRiders = $this->getAllRidersData();
        $radiusKm = $radiusMeters / 1000;

        $result = [];
        foreach ($allRiders as $rider) {
            // Support both lat/lng and latitude/longitude formats
            $riderLat = $rider['lat'] ?? $rider[Rider::COLUMN_LATITUDE] ?? null;
            $riderLng = $rider['lng'] ?? $rider[Rider::COLUMN_LONGITUDE] ?? null;

            if ($riderLat === null || $riderLng === null) {
                continue;
            }

            $distance = $this->calculateDistance($lat, $lng, (float) $riderLat, (float) $riderLng);

            if ($distance <= $radiusKm) {
                $result[] = array_merge($rider, ['distance' => $distance * 1000]);
            }
        }

        usort($result, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return $result;
    }

    private function getRidersByStatusRedis(string $status): array
    {
        $riderIds = $this->getAllRiderIds();
        $result = [];

        foreach ($riderIds as $riderId) {
            $data = $this->getRiderData($riderId);
            if ($data && ($data['status'] ?? '') === $status) {
                $result[] = $data;
            }
        }

        return $result;
    }

    private function getRidersByStatusInMemory(string $status): array
    {
        $allRiders = $this->getAllRidersData();

        return array_values(array_filter($allRiders, fn ($rider) => ($rider['status'] ?? '') === $status));
    }

    private function getAllRiderIds(): array
    {
        if ($this->isRedisDriver()) {
            // Get cache connection (not default Redis connection)
            $cacheConnection = config('cache.stores.redis.connection', 'cache');
            $redis = Redis::connection($cacheConnection);

            // Get all keys matching pattern *:rider:*
            // This matches both tagged and non-tagged cache keys
            $pattern = '*:' . $this->scope() . ':*';
            $keys = $redis->keys($pattern);

            // Extract rider IDs from keys
            $riderIds = [];
            foreach ($keys as $key) {
                // Skip tag metadata keys
                if (str_contains($key, ':tag:')) {
                    continue;
                }

                // Extract ID from key like "ebh_database_ebh_cache_hash:rider:123"
                if (preg_match('/:' . $this->scope() . ':(\d+)$/', $key, $matches)) {
                    $riderIds[] = (int) $matches[1];
                }
            }

            return array_unique($riderIds);
        }

        return [];
    }

    private function getAllRidersData(): array
    {
        $riderIds = $this->getAllRiderIds();
        $result = [];

        foreach ($riderIds as $riderId) {
            $data = $this->getRiderData($riderId);
            if ($data) {
                $result[] = $data;
            }
        }

        return $result;
    }

    private function getRiderData(int $riderId): ?array
    {
        $key = $this->makeKey($riderId);

        // Use tags if supported, otherwise plain cache
        if ($this->supportsTags()) {
            return Cache::tags($this->tags())->get($key);
        }

        return Cache::get($key);
    }

    private function storeInGeoSet($riderId, float $lat, float $lng): void
    {
        $cacheConnection = config('cache.stores.redis.connection', 'cache');
        Redis::connection($cacheConnection)->geoadd($this->geoKey(), $lng, $lat, (string) $riderId);
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // km
    }

    private function geoKey(): string
    {
        return $this->scope() . ':geo';
    }

    private function isRedisDriver(): bool
    {
        return config('cache.default') === 'redis';
    }
}
