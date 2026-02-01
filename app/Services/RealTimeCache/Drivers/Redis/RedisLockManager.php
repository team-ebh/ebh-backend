<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Redis;

use App\Services\RealTimeCache\Contracts\LockManagerInterface;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Lock Manager
 *
 * Implements distributed locking using Redis SET NX PX
 * Used to prevent race conditions in rider assignment
 *
 * Keys:
 * - {prefix}lock:{key} -> STRING (owner) with TTL
 */
class RedisLockManager implements LockManagerInterface
{
    private const string LOCK_PREFIX = 'lock:';

    private readonly string $prefix;

    private readonly int $defaultTtlMs;

    public function __construct()
    {
        $this->prefix = config('realtime-cache.redis.prefix', 'rtc:');
        $this->defaultTtlMs = config('realtime-cache.lock.ttl', 5000);
    }

    public function acquire(string $key, string $owner, ?int $ttlMs = null): bool
    {
        $ttlMs = $ttlMs ?? $this->defaultTtlMs;

        // SET key owner NX PX ttlMs
        $result = $this->redis()->set(
            $this->lockKey($key),
            $owner,
            'PX',
            $ttlMs,
            'NX'
        );

        return $result !== null;
    }

    public function release(string $key, string $owner): bool
    {
        // Lua script for atomic check-and-delete
        $script = <<<'LUA'
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('del', KEYS[1])
            else
                return 0
            end
        LUA;

        $result = $this->redis()->eval($script, 1, $this->lockKey($key), $owner);

        return $result === 1;
    }

    public function forceRelease(string $key): void
    {
        $this->redis()->del($this->lockKey($key));
    }

    public function isLocked(string $key): bool
    {
        return (bool) $this->redis()->exists($this->lockKey($key));
    }

    public function getOwner(string $key): ?string
    {
        $owner = $this->redis()->get($this->lockKey($key));

        return $owner !== false ? $owner : null;
    }

    public function extend(string $key, string $owner, ?int $ttlMs = null): bool
    {
        $ttlMs = $ttlMs ?? $this->defaultTtlMs;

        // Lua script for atomic check-and-extend
        $script = <<<'LUA'
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('pexpire', KEYS[1], ARGV[2])
            else
                return 0
            end
        LUA;

        $result = $this->redis()->eval($script, 1, $this->lockKey($key), $owner, $ttlMs);

        return $result === 1;
    }

    public function lockRider(int $riderId, string $owner, ?int $ttlMs = null): bool
    {
        return $this->acquire("rider:{$riderId}", $owner, $ttlMs);
    }

    public function unlockRider(int $riderId, string $owner): bool
    {
        return $this->release("rider:{$riderId}", $owner);
    }

    public function lockTrip(int $tripId, string $owner, ?int $ttlMs = null): bool
    {
        return $this->acquire("trip:{$tripId}", $owner, $ttlMs);
    }

    public function unlockTrip(int $tripId, string $owner): bool
    {
        return $this->release("trip:{$tripId}", $owner);
    }

    private function redis(): Connection
    {
        $connection = config('realtime-cache.redis.connection', 'default');

        return Redis::connection($connection);
    }

    private function lockKey(string $key): string
    {
        return $this->prefix . self::LOCK_PREFIX . $key;
    }
}
