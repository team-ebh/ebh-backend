<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Database;

use App\Services\RealTimeCache\Contracts\LockManagerInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Database Lock Manager (Fallback)
 *
 * Uses Laravel's cache-based atomic locks when Redis is unavailable.
 */
class DatabaseLockManager implements LockManagerInterface
{
    private const string LOCK_PREFIX = 'rtc_lock:';

    private readonly int $defaultTtlMs;

    public function __construct()
    {
        $this->defaultTtlMs = config('realtime-cache.lock.ttl', 5000);
    }

    public function acquire(string $key, string $owner, ?int $ttlMs = null): bool
    {
        $ttlMs = $ttlMs ?? $this->defaultTtlMs;
        $ttlSeconds = (int) ceil($ttlMs / 1000);

        $lock = Cache::lock($this->lockKey($key), $ttlSeconds, $owner);

        return $lock->get();
    }

    public function release(string $key, string $owner): bool
    {
        $lock = Cache::lock($this->lockKey($key), owner: $owner);

        if ($this->getOwner($key) === $owner) {
            $lock->forceRelease();

            return true;
        }

        return false;
    }

    public function forceRelease(string $key): void
    {
        $lock = Cache::lock($this->lockKey($key));
        $lock->forceRelease();
    }

    public function isLocked(string $key): bool
    {
        return Cache::has($this->lockKey($key));
    }

    public function getOwner(string $key): ?string
    {
        return Cache::get($this->lockKey($key));
    }

    public function extend(string $key, string $owner, ?int $ttlMs = null): bool
    {
        if ($this->getOwner($key) !== $owner) {
            return false;
        }

        $ttlMs = $ttlMs ?? $this->defaultTtlMs;
        $ttlSeconds = (int) ceil($ttlMs / 1000);

        Cache::put($this->lockKey($key), $owner, $ttlSeconds);

        return true;
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

    private function lockKey(string $key): string
    {
        return self::LOCK_PREFIX . $key;
    }
}
