<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Redis;

use App\Services\RealTimeCache\Contracts\StatusStorageInterface;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Status Storage
 *
 * Uses Redis HASH for status + metadata with TTL for automatic offline detection
 * Also maintains SETs for quick lookup of riders by status
 *
 * Keys:
 * - {prefix}rider:{id}:status -> HASH with TTL {status, vehicle_id, last_seen, ...}
 * - {prefix}riders:online -> SET of online rider IDs (includes busy)
 * - {prefix}riders:busy -> SET of busy rider IDs
 */
class RedisStatusStorage implements StatusStorageInterface
{
    private const string RIDER_STATUS_PREFIX = 'rider:';

    private const string RIDER_STATUS_SUFFIX = ':status';

    private const string ONLINE_SET = 'riders:online';

    private const string BUSY_SET = 'riders:busy';

    private readonly string $prefix;

    private readonly int $defaultTtl;

    public function __construct()
    {
        $this->prefix = config('realtime-cache.redis.prefix', 'rtc:');
        $this->defaultTtl = config('realtime-cache.rider_status.ttl', 60);
    }

    public function setStatus(int $riderId, string $status, array $meta = [], ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;

        $data = array_merge($meta, [
            'status' => $status,
            'last_seen' => time(),
        ]);

        $this->redis()->pipeline(function ($pipe) use ($riderId, $status, $data, $ttl) {
            // Store status hash with TTL
            $pipe->hmset($this->statusKey($riderId), $data);
            $pipe->expire($this->statusKey($riderId), $ttl);

            // Update status sets
            $this->updateStatusSets($pipe, $riderId, $status);
        });
    }

    public function getStatus(int $riderId): ?string
    {
        $status = $this->redis()->hget($this->statusKey($riderId), 'status');

        if ($status === null || $status === false) {
            // Expired, clean up sets
            $this->removeFromAllSets($riderId);

            return null;
        }

        return $status;
    }

    public function getMeta(int $riderId): ?array
    {
        $data = $this->redis()->hgetall($this->statusKey($riderId));

        if (empty($data)) {
            return null;
        }

        return $data;
    }

    public function heartbeat(int $riderId, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;

        if (! $this->redis()->exists($this->statusKey($riderId))) {
            return false;
        }

        $this->redis()->pipeline(function ($pipe) use ($riderId, $ttl) {
            $pipe->hset($this->statusKey($riderId), 'last_seen', time());
            $pipe->expire($this->statusKey($riderId), $ttl);
        });

        return true;
    }

    public function setOnline(int $riderId, array $meta = []): void
    {
        $this->setStatus($riderId, self::STATUS_ONLINE, $meta);
    }

    public function setBusy(int $riderId, array $meta = []): void
    {
        $this->setStatus($riderId, self::STATUS_BUSY, $meta);
    }

    public function setOffline(int $riderId): void
    {
        $this->redis()->pipeline(function ($pipe) use ($riderId) {
            $pipe->del($this->statusKey($riderId));
            $this->removeFromAllSets($riderId, $pipe);
        });
    }

    public function isOnline(int $riderId): bool
    {
        return $this->getStatus($riderId) !== null;
    }

    public function isBusy(int $riderId): bool
    {
        return $this->getStatus($riderId) === self::STATUS_BUSY;
    }

    public function getOnlineRiderIds(): array
    {
        return $this->getValidRiderIdsFromSet(self::ONLINE_SET);
    }

    public function getReadyRiderIds(): array
    {
        $onlineIds = $this->getOnlineRiderIds();
        $busyIds = $this->getBusyRiderIds();

        return array_values(array_diff($onlineIds, $busyIds));
    }

    public function getBusyRiderIds(): array
    {
        return $this->getValidRiderIdsFromSet(self::BUSY_SET);
    }

    public function countOnline(): int
    {
        return count($this->getOnlineRiderIds());
    }

    public function countReady(): int
    {
        return count($this->getReadyRiderIds());
    }

    public function countBusy(): int
    {
        return count($this->getBusyRiderIds());
    }

    public function flush(): void
    {
        $this->redis()->pipeline(function ($pipe) {
            // Get all online riders to delete their status keys
            $onlineIds = $this->redis()->smembers($this->prefix . self::ONLINE_SET);

            foreach ($onlineIds as $riderId) {
                $pipe->del($this->statusKey((int) $riderId));
            }

            $pipe->del($this->prefix . self::ONLINE_SET);
            $pipe->del($this->prefix . self::BUSY_SET);
        });
    }

    /**
     * Get valid rider IDs from a set (filters out expired)
     *
     * @return array<int>
     */
    private function getValidRiderIdsFromSet(string $setKey): array
    {
        $members = $this->redis()->smembers($this->prefix . $setKey);

        if (empty($members)) {
            return [];
        }

        $validIds = [];

        foreach ($members as $riderId) {
            $riderId = (int) $riderId;

            if ($this->redis()->exists($this->statusKey($riderId))) {
                $validIds[] = $riderId;
            } else {
                // Clean up expired
                $this->removeFromAllSets($riderId);
            }
        }

        return $validIds;
    }

    /**
     * Update status sets based on new status
     *
     * @param  mixed  $pipe  Redis pipeline
     */
    private function updateStatusSets(mixed $pipe, int $riderId, string $status): void
    {
        // Always add to online set (both online and busy are "online")
        $pipe->sadd($this->prefix . self::ONLINE_SET, $riderId);

        // Manage busy set
        if ($status === self::STATUS_BUSY) {
            $pipe->sadd($this->prefix . self::BUSY_SET, $riderId);
        } else {
            $pipe->srem($this->prefix . self::BUSY_SET, $riderId);
        }
    }

    /**
     * Remove rider from all status sets
     */
    private function removeFromAllSets(int $riderId, mixed $pipe = null): void
    {
        $redis = $pipe ?? $this->redis();

        $redis->srem($this->prefix . self::ONLINE_SET, $riderId);
        $redis->srem($this->prefix . self::BUSY_SET, $riderId);
    }

    private function redis(): Connection
    {
        $connection = config('realtime-cache.redis.connection', 'default');

        return Redis::connection($connection);
    }

    private function statusKey(int $riderId): string
    {
        return $this->prefix . self::RIDER_STATUS_PREFIX . $riderId . self::RIDER_STATUS_SUFFIX;
    }
}
