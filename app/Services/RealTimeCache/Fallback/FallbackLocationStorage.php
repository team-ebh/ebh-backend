<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Fallback;

use App\Services\RealTimeCache\Contracts\LocationStorageInterface;
use App\Services\RealTimeCache\ValueObjects\Coordinate;
use App\Services\RealTimeCache\ValueObjects\Distance;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback Location Storage (Read-Through Cache)
 *
 * - Tries Redis first
 * - If not found or Redis fails, checks Database
 * - If found in Database, syncs back to Redis
 */
class FallbackLocationStorage implements LocationStorageInterface
{
    public function __construct(
        private readonly LocationStorageInterface $primary,
        private readonly LocationStorageInterface $fallback,
        private readonly RedisHealthChecker $healthChecker,
    ) {}

    public function store(int $riderId, Coordinate $coordinate, ?int $ttl = null): void
    {
        // Always try to store in both (Redis is primary)
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->store($riderId, $coordinate, $ttl);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'store');
                $this->fallback->store($riderId, $coordinate, $ttl);
            }
        } else {
            $this->fallback->store($riderId, $coordinate, $ttl);
        }
    }

    public function get(int $riderId): ?Coordinate
    {
        // Try Redis first
        if ($this->healthChecker->isHealthy()) {
            try {
                $result = $this->primary->get($riderId);

                if ($result !== null) {
                    return $result;
                }
            } catch (Throwable $e) {
                $this->handleFailure($e, 'get');
            }
        }

        // Not in Redis or Redis failed, check Database
        $result = $this->fallback->get($riderId);

        // Sync to Redis if found
        if ($result !== null && $this->healthChecker->isHealthy()) {
            try {
                $this->primary->store($riderId, $result);
            } catch (Throwable) {
                // Ignore sync failure
            }
        }

        return $result;
    }

    public function remove(int $riderId): void
    {
        // Remove from both
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->remove($riderId);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'remove');
            }
        }

        $this->fallback->remove($riderId);
    }

    public function findNearby(Coordinate $center, Distance $radius, ?int $limit = null): array
    {
        $redisResults = [];
        $redisRiderIds = [];

        // Try Redis first
        if ($this->healthChecker->isHealthy()) {
            try {
                $redisResults = $this->primary->findNearby($center, $radius, $limit);
                $redisRiderIds = array_column($redisResults, 'rider_id');
            } catch (Throwable $e) {
                $this->handleFailure($e, 'findNearby');
            }
        }

        // Also check Database for riders not in Redis
        $dbResults = $this->fallback->findNearby($center, $radius, $limit);

        // Merge results (Redis takes priority for duplicates)
        $merged = $redisResults;

        foreach ($dbResults as $dbRider) {
            if (! in_array($dbRider['rider_id'], $redisRiderIds, true)) {
                $merged[] = $dbRider;

                // Sync to Redis
                if ($this->healthChecker->isHealthy()) {
                    try {
                        $this->primary->store($dbRider['rider_id'], $dbRider['coordinate']);
                    } catch (Throwable) {
                        // Ignore sync failure
                    }
                }
            }
        }

        // Sort by distance and apply limit
        usort($merged, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        if ($limit !== null) {
            $merged = array_slice($merged, 0, $limit);
        }

        return $merged;
    }

    public function getAllRiderIds(): array
    {
        $redisIds = [];

        if ($this->healthChecker->isHealthy()) {
            try {
                $redisIds = $this->primary->getAllRiderIds();
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getAllRiderIds');
            }
        }

        $dbIds = $this->fallback->getAllRiderIds();

        // Merge and unique
        return array_values(array_unique(array_merge($redisIds, $dbIds)));
    }

    public function exists(int $riderId): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                if ($this->primary->exists($riderId)) {
                    return true;
                }
            } catch (Throwable $e) {
                $this->handleFailure($e, 'exists');
            }
        }

        return $this->fallback->exists($riderId);
    }

    public function count(): int
    {
        return count($this->getAllRiderIds());
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

        Log::warning('RealTimeCache: Redis location storage failed, using database fallback', [
            'method' => $method,
            'error' => $e->getMessage(),
        ]);
    }
}
