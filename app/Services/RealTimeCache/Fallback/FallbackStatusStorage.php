<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Fallback;

use App\Services\RealTimeCache\Contracts\StatusStorageInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback Status Storage (Read-Through Cache)
 *
 * - Tries Redis first
 * - If not found or Redis fails, checks Database
 * - If found in Database, syncs back to Redis
 */
class FallbackStatusStorage implements StatusStorageInterface
{
    public function __construct(
        private readonly StatusStorageInterface $primary,
        private readonly StatusStorageInterface $fallback,
        private readonly RedisHealthChecker $healthChecker,
    ) {}

    public function setStatus(int $riderId, string $status, array $meta = [], ?int $ttl = null): void
    {
        // Always try to store in Redis first
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->setStatus($riderId, $status, $meta, $ttl);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'setStatus');
            }
        }

        $this->fallback->setStatus($riderId, $status, $meta, $ttl);
    }

    public function getStatus(int $riderId): ?string
    {
        // Try Redis first
        if ($this->healthChecker->isHealthy()) {
            try {
                $result = $this->primary->getStatus($riderId);

                if ($result !== null) {
                    return $result;
                }
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getStatus');
            }
        }

        // Not in Redis, check Database
        $result = $this->fallback->getStatus($riderId);

        // Sync to Redis if found
        if ($result !== null && $this->healthChecker->isHealthy()) {
            try {
                $meta = $this->fallback->getMeta($riderId) ?? [];
                $this->primary->setStatus($riderId, $result, $meta);
            } catch (Throwable) {
                // Ignore sync failure
            }
        }

        return $result;
    }

    public function getMeta(int $riderId): ?array
    {
        // Try Redis first
        if ($this->healthChecker->isHealthy()) {
            try {
                $result = $this->primary->getMeta($riderId);

                if ($result !== null) {
                    return $result;
                }
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getMeta');
            }
        }

        // Not in Redis, check Database
        $result = $this->fallback->getMeta($riderId);

        // Sync to Redis if found
        if ($result !== null && $this->healthChecker->isHealthy()) {
            try {
                $status = $result['status'] ?? self::STATUS_ONLINE;
                $this->primary->setStatus($riderId, $status, $result);
            } catch (Throwable) {
                // Ignore sync failure
            }
        }

        return $result;
    }

    public function heartbeat(int $riderId, ?int $ttl = null): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                if ($this->primary->heartbeat($riderId, $ttl)) {
                    return true;
                }

                // Not in Redis, check if in DB and sync
                if ($this->fallback->isOnline($riderId)) {
                    $meta = $this->fallback->getMeta($riderId) ?? [];
                    $status = $meta['status'] ?? self::STATUS_ONLINE;
                    $this->primary->setStatus($riderId, $status, $meta, $ttl);

                    return true;
                }

                return false;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'heartbeat');
            }
        }

        return $this->fallback->heartbeat($riderId, $ttl);
    }

    public function setOnline(int $riderId, array $meta = []): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->setOnline($riderId, $meta);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'setOnline');
            }
        }

        $this->fallback->setOnline($riderId, $meta);
    }

    public function setBusy(int $riderId, array $meta = []): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->setBusy($riderId, $meta);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'setBusy');
            }
        }

        $this->fallback->setBusy($riderId, $meta);
    }

    public function setOffline(int $riderId): void
    {
        // Remove from both
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->setOffline($riderId);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'setOffline');
            }
        }

        $this->fallback->setOffline($riderId);
    }

    public function isOnline(int $riderId): bool
    {
        // Try Redis first
        if ($this->healthChecker->isHealthy()) {
            try {
                if ($this->primary->isOnline($riderId)) {
                    return true;
                }
            } catch (Throwable $e) {
                $this->handleFailure($e, 'isOnline');
            }
        }

        // Check Database
        $isOnline = $this->fallback->isOnline($riderId);

        // Sync to Redis if online
        if ($isOnline && $this->healthChecker->isHealthy()) {
            try {
                $meta = $this->fallback->getMeta($riderId) ?? [];
                $status = $meta['status'] ?? self::STATUS_ONLINE;
                $this->primary->setStatus($riderId, $status, $meta);
            } catch (Throwable) {
                // Ignore
            }
        }

        return $isOnline;
    }

    public function isBusy(int $riderId): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                if ($this->primary->isBusy($riderId)) {
                    return true;
                }

                // Check if even online in Redis
                if ($this->primary->isOnline($riderId)) {
                    return false; // Online but not busy
                }
            } catch (Throwable $e) {
                $this->handleFailure($e, 'isBusy');
            }
        }

        return $this->fallback->isBusy($riderId);
    }

    public function getOnlineRiderIds(): array
    {
        $redisIds = [];

        if ($this->healthChecker->isHealthy()) {
            try {
                $redisIds = $this->primary->getOnlineRiderIds();
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getOnlineRiderIds');
            }
        }

        $dbIds = $this->fallback->getOnlineRiderIds();

        // Merge and unique
        $allIds = array_values(array_unique(array_merge($redisIds, $dbIds)));

        // Sync missing ones to Redis
        $missingIds = array_diff($dbIds, $redisIds);
        if (! empty($missingIds) && $this->healthChecker->isHealthy()) {
            foreach ($missingIds as $riderId) {
                try {
                    $meta = $this->fallback->getMeta($riderId) ?? [];
                    $status = $meta['status'] ?? self::STATUS_ONLINE;
                    $this->primary->setStatus($riderId, $status, $meta);
                } catch (Throwable) {
                    // Ignore
                }
            }
        }

        return $allIds;
    }

    public function getReadyRiderIds(): array
    {
        $redisIds = [];

        if ($this->healthChecker->isHealthy()) {
            try {
                $redisIds = $this->primary->getReadyRiderIds();
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getReadyRiderIds');
            }
        }

        $dbIds = $this->fallback->getReadyRiderIds();

        return array_values(array_unique(array_merge($redisIds, $dbIds)));
    }

    public function getBusyRiderIds(): array
    {
        $redisIds = [];

        if ($this->healthChecker->isHealthy()) {
            try {
                $redisIds = $this->primary->getBusyRiderIds();
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getBusyRiderIds');
            }
        }

        $dbIds = $this->fallback->getBusyRiderIds();

        return array_values(array_unique(array_merge($redisIds, $dbIds)));
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
        try {
            $this->primary->flush();
        } catch (Throwable) {
            // Ignore
        }

        $this->fallback->flush();
    }

    private function handleFailure(Throwable $e, string $method): void
    {
        $this->healthChecker->markUnhealthy();

        Log::warning('RealTimeCache: Redis status storage failed, using database fallback', [
            'method' => $method,
            'error' => $e->getMessage(),
        ]);
    }
}
