<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Base Cache Service
 *
 * Abstract base class for all cache services with dynamic context support.
 * Context can be: customer, rider, admin, or null (global)
 *
 * Example usage:
 * - TripCache::customer($id, fn() => ...) // trip:customer:123
 * - TripCache::rider($id, fn() => ...)    // trip:rider:123
 * - ProductCache::get($id, fn() => ...)   // product:123 (no context)
 */
abstract class BaseCache
{
    /**
     * Context (customer, rider, admin, or null for global)
     */
    protected ?string $context = null;

    /**
     * Cache scope/prefix (e.g., 'trip', 'notification', 'product')
     * Must be implemented by child classes
     */
    abstract protected function scope(): string;

    /**
     * TTL (Time To Live) in seconds
     * Must be implemented by child classes
     */
    abstract protected function ttl(): int;

    /**
     * Get TTL value (public accessor)
     */
    public function getTtl(): int
    {
        return $this->ttl();
    }

    /**
     * Count total entries in this cache scope
     */
    public function countEntries(): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        if (! $this->supportsTags()) {
            return 0;
        }

        // Get cache connection
        $cacheConnection = config('cache.stores.redis.connection', 'cache');
        $redis = Redis::connection($cacheConnection);

        // Pattern to match all keys for this scope
        $pattern = '*:' . $this->scope() . ':*';
        $keys = $redis->keys($pattern);

        // Filter out tag metadata keys (they contain 'tag:' in the key)
        $dataKeys = array_filter($keys, function ($key) {
            return ! str_contains($key, 'tag:') && ! str_contains($key, ':tag');
        });

        return count($dataKeys);
    }

    /**
     * Check if caching is enabled for this scope
     * Override in child class to disable caching
     */
    protected function enabled(): bool
    {
        return true;
    }

    /**
     * Check if current cache driver supports tags
     */
    protected function supportsTags(): bool
    {
        return Cache::supportsTags();
    }

    /**
     * Get cache tags (used for group invalidation)
     */
    protected function tags(): array
    {
        $tags = [$this->scope()];

        if ($this->context) {
            $tags[] = $this->context;
        }

        return $tags;
    }

    /**
     * Set context dynamically (customer, rider, admin)
     *
     * @return static New instance with context
     */
    public function for(string $context): static
    {
        $instance = clone $this;
        $instance->context = $context;

        return $instance;
    }

    /**
     * Get or calculate value with caching
     * Primary: Cache (Redis/Memcached/etc), Fallback: callback (typically database)
     *
     * @throws \Throwable
     */
    public function get($id, callable $callback)
    {
        if (! $this->enabled()) {
            return $callback();
        }

        return safeProcess()
            ->onFailed(fn () => $callback())
            ->do(function () use ($id, $callback) {
                $key = $this->makeKey($id);

                // Use tags if supported (Redis, Memcached), otherwise plain cache
                if ($this->supportsTags()) {
                    return Cache::tags($this->tags())
                        ->remember($key, $this->ttl(), $callback);
                }

                return Cache::remember($key, $this->ttl(), $callback);
            });
    }

    /**
     * Clear cache for specific ID
     */
    public function forget($id): void
    {
        if (! $this->enabled()) {
            return;
        }

        $key = $this->makeKey($id);

        // Use tags if supported, otherwise plain forget
        if ($this->supportsTags()) {
            Cache::tags($this->tags())->forget($key);
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Flush all cache for this scope
     *
     * Note: Group flush only works with tag-supporting drivers (Redis, Memcached)
     * For other drivers (database, file), this method will do nothing
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
        // For non-tag drivers: can't flush by scope, would need to flush entire cache
        // We skip this to avoid clearing unrelated cache entries
    }

    /**
     * Generate cache key
     * Format: scope:context:id or scope:id (if no context)
     */
    protected function makeKey($id): string
    {
        $parts = [$this->scope()];

        if ($this->context) {
            $parts[] = $this->context;
        }

        $parts[] = $id;

        return implode(':', $parts);
    }

    /**
     * Static helper: Get with customer context
     */
    public static function customer($id, callable $callback)
    {
        return (new static)->for('customer')->get($id, $callback);
    }

    /**
     * Static helper: Get with rider context
     */
    public static function rider($id, callable $callback)
    {
        return (new static)->for('rider')->get($id, $callback);
    }

    /**
     * Static helper: Clear customer cache
     */
    public static function forgetCustomer($id): void
    {
        (new static)->for('customer')->forget($id);
    }

    /**
     * Static helper: Clear rider cache
     */
    public static function forgetRider($id): void
    {
        (new static)->for('rider')->forget($id);
    }

    /**
     * Static helper: Clear both customer and rider cache
     */
    public static function forgetBoth($customerId, $riderId): void
    {
        if ($customerId) {
            static::forgetCustomer($customerId);
        }

        if ($riderId) {
            static::forgetRider($riderId);
        }
    }
}
