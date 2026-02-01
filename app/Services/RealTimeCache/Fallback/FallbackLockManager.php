<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Fallback;

use App\Services\RealTimeCache\Contracts\LockManagerInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback Lock Manager
 *
 * Tries Redis first, falls back to Database locks if Redis fails.
 */
class FallbackLockManager implements LockManagerInterface
{
    public function __construct(
        private readonly LockManagerInterface $primary,
        private readonly LockManagerInterface $fallback,
        private readonly RedisHealthChecker $healthChecker,
    ) {}

    public function acquire(string $key, string $owner, ?int $ttlMs = null): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->acquire($key, $owner, $ttlMs);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'acquire');
            }
        }

        return $this->fallback->acquire($key, $owner, $ttlMs);
    }

    public function release(string $key, string $owner): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->release($key, $owner);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'release');
            }
        }

        return $this->fallback->release($key, $owner);
    }

    public function forceRelease(string $key): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->forceRelease($key);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'forceRelease');
            }
        }

        $this->fallback->forceRelease($key);
    }

    public function isLocked(string $key): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->isLocked($key);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'isLocked');
            }
        }

        return $this->fallback->isLocked($key);
    }

    public function getOwner(string $key): ?string
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->getOwner($key);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getOwner');
            }
        }

        return $this->fallback->getOwner($key);
    }

    public function extend(string $key, string $owner, ?int $ttlMs = null): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->extend($key, $owner, $ttlMs);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'extend');
            }
        }

        return $this->fallback->extend($key, $owner, $ttlMs);
    }

    public function lockRider(int $riderId, string $owner, ?int $ttlMs = null): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->lockRider($riderId, $owner, $ttlMs);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'lockRider');
            }
        }

        return $this->fallback->lockRider($riderId, $owner, $ttlMs);
    }

    public function unlockRider(int $riderId, string $owner): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->unlockRider($riderId, $owner);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'unlockRider');
            }
        }

        return $this->fallback->unlockRider($riderId, $owner);
    }

    public function lockTrip(int $tripId, string $owner, ?int $ttlMs = null): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->lockTrip($tripId, $owner, $ttlMs);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'lockTrip');
            }
        }

        return $this->fallback->lockTrip($tripId, $owner, $ttlMs);
    }

    public function unlockTrip(int $tripId, string $owner): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->unlockTrip($tripId, $owner);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'unlockTrip');
            }
        }

        return $this->fallback->unlockTrip($tripId, $owner);
    }

    private function handleFailure(Throwable $e, string $method): void
    {
        $this->healthChecker->markUnhealthy();

        Log::warning('RealTimeCache: Redis lock manager failed, using database fallback', [
            'method' => $method,
            'error' => $e->getMessage(),
        ]);
    }
}
