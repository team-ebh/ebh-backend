<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Redis;

use App\Services\RealTimeCache\Contracts\TripStorageInterface;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Trip Storage
 *
 * Caches trip data during active rides
 *
 * Keys:
 * - {prefix}trip:{id} -> HASH (trip data)
 * - {prefix}rider:{id}:trip -> STRING (active trip ID)
 * - {prefix}customer:{id}:trip -> STRING (active trip ID)
 * - {prefix}trips:active -> SET (all active trip IDs)
 */
class RedisTripStorage implements TripStorageInterface
{
    private const string TRIP_PREFIX = 'trip:';

    private const string RIDER_TRIP_SUFFIX = ':trip';

    private const string CUSTOMER_TRIP_PREFIX = 'customer:';

    private const string ACTIVE_TRIPS_SET = 'trips:active';

    private readonly string $prefix;

    private readonly array $ttlConfig;

    public function __construct()
    {
        $this->prefix = config('realtime-cache.redis.prefix', 'rtc:');
        $this->ttlConfig = config('realtime-cache.trip.ttl', []);
    }

    public function store(int $tripId, array $data, string $status = 'pending'): void
    {
        $ttl = $this->getTtlForStatus($status);
        $data['_status'] = $status;
        $data['_created_at'] = time();

        $this->redis()->pipeline(function ($pipe) use ($tripId, $data, $ttl) {
            $pipe->hmset($this->tripKey($tripId), $this->flattenForRedis($data));
            $pipe->expire($this->tripKey($tripId), $ttl);
            $pipe->sadd($this->prefix . self::ACTIVE_TRIPS_SET, $tripId);
        });
    }

    public function get(int $tripId): ?array
    {
        $data = $this->redis()->hgetall($this->tripKey($tripId));

        if (empty($data)) {
            // Clean up from active set
            $this->redis()->srem($this->prefix . self::ACTIVE_TRIPS_SET, $tripId);

            return null;
        }

        return $this->unflattenFromRedis($data);
    }

    public function update(int $tripId, array $data): void
    {
        if (! $this->exists($tripId)) {
            return;
        }

        $data['_updated_at'] = time();

        $this->redis()->hmset($this->tripKey($tripId), $this->flattenForRedis($data));
    }

    public function updateStatus(int $tripId, string $status): void
    {
        if (! $this->exists($tripId)) {
            return;
        }

        $ttl = $this->getTtlForStatus($status);

        $this->redis()->pipeline(function ($pipe) use ($tripId, $status, $ttl) {
            $pipe->hset($this->tripKey($tripId), '_status', $status);
            $pipe->hset($this->tripKey($tripId), '_updated_at', time());
            $pipe->expire($this->tripKey($tripId), $ttl);
        });
    }

    public function remove(int $tripId): void
    {
        // Get rider and customer IDs before removing
        $data = $this->get($tripId);

        $this->redis()->pipeline(function ($pipe) use ($tripId, $data) {
            $pipe->del($this->tripKey($tripId));
            $pipe->srem($this->prefix . self::ACTIVE_TRIPS_SET, $tripId);

            // Clear active trip mappings if they exist
            if (isset($data['rider_id'])) {
                $pipe->del($this->riderTripKey((int) $data['rider_id']));
            }
            if (isset($data['customer_id'])) {
                $pipe->del($this->customerTripKey((int) $data['customer_id']));
            }
        });
    }

    public function exists(int $tripId): bool
    {
        return (bool) $this->redis()->exists($this->tripKey($tripId));
    }

    public function setRiderActiveTrip(int $riderId, int $tripId): void
    {
        $ttl = $this->getTtlForStatus('picked_up'); // Use longest TTL

        $this->redis()->setex($this->riderTripKey($riderId), $ttl, $tripId);
    }

    public function getRiderActiveTrip(int $riderId): ?int
    {
        $tripId = $this->redis()->get($this->riderTripKey($riderId));

        return $tripId !== null ? (int) $tripId : null;
    }

    public function clearRiderActiveTrip(int $riderId): void
    {
        $this->redis()->del($this->riderTripKey($riderId));
    }

    public function setCustomerActiveTrip(int $customerId, int $tripId): void
    {
        $ttl = $this->getTtlForStatus('picked_up');

        $this->redis()->setex($this->customerTripKey($customerId), $ttl, $tripId);
    }

    public function getCustomerActiveTrip(int $customerId): ?int
    {
        $tripId = $this->redis()->get($this->customerTripKey($customerId));

        return $tripId !== null ? (int) $tripId : null;
    }

    public function clearCustomerActiveTrip(int $customerId): void
    {
        $this->redis()->del($this->customerTripKey($customerId));
    }

    public function getAllTripIds(): array
    {
        $tripIds = $this->redis()->smembers($this->prefix . self::ACTIVE_TRIPS_SET);

        if (empty($tripIds)) {
            return [];
        }

        // Filter out expired trips
        $validIds = [];
        foreach ($tripIds as $tripId) {
            $tripId = (int) $tripId;
            if ($this->exists($tripId)) {
                $validIds[] = $tripId;
            } else {
                $this->redis()->srem($this->prefix . self::ACTIVE_TRIPS_SET, $tripId);
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
        $tripIds = $this->redis()->smembers($this->prefix . self::ACTIVE_TRIPS_SET);

        $this->redis()->pipeline(function ($pipe) use ($tripIds) {
            foreach ($tripIds as $tripId) {
                $tripId = (int) $tripId;
                $pipe->del($this->tripKey($tripId));
            }

            $pipe->del($this->prefix . self::ACTIVE_TRIPS_SET);
        });
    }

    private function getTtlForStatus(string $status): int
    {
        return $this->ttlConfig[$status] ?? 3600;
    }

    /**
     * Flatten nested array for Redis HMSET
     */
    private function flattenForRedis(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $result[$key] = $value ? '1' : '0';
            } else {
                $result[$key] = (string) $value;
            }
        }

        return $result;
    }

    /**
     * Unflatten data from Redis HGETALL
     */
    private function unflattenFromRedis(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            // Try to decode JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $result[$key] = $decoded;
            } elseif (is_numeric($value)) {
                // Convert numeric strings
                $result[$key] = str_contains($value, '.') ? (float) $value : (int) $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function redis(): Connection
    {
        $connection = config('realtime-cache.redis.connection', 'default');

        return Redis::connection($connection);
    }

    private function tripKey(int $tripId): string
    {
        return $this->prefix . self::TRIP_PREFIX . $tripId;
    }

    private function riderTripKey(int $riderId): string
    {
        return $this->prefix . 'rider:' . $riderId . self::RIDER_TRIP_SUFFIX;
    }

    private function customerTripKey(int $customerId): string
    {
        return $this->prefix . self::CUSTOMER_TRIP_PREFIX . $customerId . self::RIDER_TRIP_SUFFIX;
    }
}
