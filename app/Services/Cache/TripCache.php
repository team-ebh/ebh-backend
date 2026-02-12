<?php

declare(strict_types=1);

namespace App\Services\Cache;

/**
 * Trip Cache
 *
 * Caches trip data for customers and riders
 *
 * Usage:
 * - TripCache::customer($customerId, fn() => [...])  // trip:customer:123
 * - TripCache::rider($riderId, fn() => [...])        // trip:rider:456
 * - TripCache::forgetCustomer($customerId)
 * - TripCache::forgetRider($riderId)
 * - TripCache::forgetBoth($customerId, $riderId)
 *
 * Typical usage for active trips:
 * - Cache active trip when fetched
 * - Clear cache when trip status changes
 * - Lazy loading ensures fresh data
 */
class TripCache extends BaseCache
{
    protected function scope(): string
    {
        return 'trip';
    }

    protected function ttl(): int
    {
        return 600; // 10 minutes (trips change frequently)
    }
}
