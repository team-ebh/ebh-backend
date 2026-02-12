<?php

declare(strict_types=1);

namespace App\Services\Cache;

/**
 * App State Cache Service
 *
 * Caches customer and rider app states
 *
 * Usage:
 * - AppStateCache::customer($id, fn() => ...) // app_state:customer:123
 * - AppStateCache::rider($id, fn() => ...)    // app_state:rider:456
 * - AppStateCache::forgetCustomer($id)
 * - AppStateCache::forgetRider($id)
 * - AppStateCache::forgetBoth($customerId, $riderId)
 */
class AppStateCache extends BaseCache
{
    protected function scope(): string
    {
        return 'app_state';
    }

    protected function ttl(): int
    {
        return 900; // 15 minutes
    }
}
