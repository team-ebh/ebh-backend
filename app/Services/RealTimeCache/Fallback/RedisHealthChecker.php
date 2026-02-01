<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Fallback;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Redis Health Checker
 *
 * Tracks Redis availability and implements circuit breaker pattern.
 * When Redis fails, it's marked as unhealthy for a cooldown period
 * to avoid repeated connection attempts.
 */
class RedisHealthChecker
{
    private const string HEALTH_CACHE_KEY = 'rtc:redis:unhealthy_until';

    /**
     * Cooldown period in seconds after Redis failure
     */
    private readonly int $cooldownSeconds;

    /**
     * In-memory cache for current request
     */
    private ?bool $isHealthyCache = null;

    public function __construct()
    {
        $this->cooldownSeconds = config('realtime-cache.fallback.cooldown_seconds', 30);
    }

    /**
     * Check if Redis is healthy and should be used
     */
    public function isHealthy(): bool
    {
        // Use in-memory cache for this request
        if ($this->isHealthyCache !== null) {
            return $this->isHealthyCache;
        }

        // Check if we're in cooldown period
        $unhealthyUntil = Cache::get(self::HEALTH_CACHE_KEY);

        if ($unhealthyUntil !== null && time() < (int) $unhealthyUntil) {
            $this->isHealthyCache = false;

            return false;
        }

        // Try to ping Redis
        try {
            $connection = config('realtime-cache.redis.connection', 'default');
            Redis::connection($connection)->ping();
            $this->isHealthyCache = true;

            // Clear unhealthy flag if it exists
            if ($unhealthyUntil !== null) {
                Cache::forget(self::HEALTH_CACHE_KEY);
            }

            return true;
        } catch (Throwable) {
            $this->markUnhealthy();

            return false;
        }
    }

    /**
     * Mark Redis as unhealthy (called on failure)
     */
    public function markUnhealthy(): void
    {
        $this->isHealthyCache = false;

        // Store using file/database cache (not Redis!)
        $unhealthyUntil = time() + $this->cooldownSeconds;

        // Use file cache driver to avoid Redis dependency
        Cache::store('file')->put(
            self::HEALTH_CACHE_KEY,
            $unhealthyUntil,
            $this->cooldownSeconds + 60
        );
    }

    /**
     * Force mark as healthy (for recovery)
     */
    public function markHealthy(): void
    {
        $this->isHealthyCache = null;
        Cache::store('file')->forget(self::HEALTH_CACHE_KEY);
    }

    /**
     * Get current status info
     */
    public function getStatus(): array
    {
        $unhealthyUntil = Cache::store('file')->get(self::HEALTH_CACHE_KEY);

        return [
            'healthy' => $this->isHealthy(),
            'in_cooldown' => $unhealthyUntil !== null && time() < (int) $unhealthyUntil,
            'cooldown_remaining' => $unhealthyUntil !== null
                ? max(0, (int) $unhealthyUntil - time())
                : 0,
        ];
    }
}
